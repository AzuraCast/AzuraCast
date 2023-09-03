<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Container\ContainerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Entity\Attributes\StableMigration;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

#[AsCommand(
    name: 'azuracast:setup:rollback',
    description: 'Roll back the database to the state associated with a certain stable release.',
)]
final class RollbackDbCommand extends AbstractDatabaseCommand
{
    use ContainerAwareTrait;
    use EnvironmentAwareTrait;

    protected function configure(): void
    {
        $this->addArgument('version', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(__('Roll Back Database'));

        // Pull migration corresponding to the stable version specified.
        try {
            $version = $input->getArgument('version');
            $migrationVersion = $this->findMigration($version);
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $this->runCommand(
            $output,
            'migrations:sync-metadata-storage'
        );

        // Attempt DB migration.
        $io->section(__('Running database migrations...'));

        // Back up current DB state.
        try {
            $dbDumpPath = $this->saveOrRestoreDatabase($io);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }

        try {
            $io->info($migrationVersion);

            $this->runCommand(
                $output,
                'migrations:migrate',
                [
                    '--allow-no-migration' => true,
                    'version' => $migrationVersion,
                ]
            );
        } catch (Exception $e) {
            // Rollback to the DB dump from earlier.
            $io->error(
                sprintf(
                    __('Database migration failed: %s'),
                    $e->getMessage()
                )
            );

            return $this->tryEmergencyRestore($io, $dbDumpPath);
        } finally {
            (new Filesystem())->remove($dbDumpPath);
        }

        $io->newLine();
        $io->success(
            sprintf(
                __('Database rolled back to stable release version "%s".'),
                $version
            )
        );
        return 0;
    }

    protected function findMigration(string $version): string
    {
        $version = trim($version);

        if (empty($version)) {
            throw new InvalidArgumentException('No version specified.');
        }

        $versionParts = explode('.', $version);
        if (3 !== count($versionParts)) {
            throw new InvalidArgumentException(
                'Invalid version specified. Version must be in the form of x.x.x, i.e. 0.19.0.'
            );
        }

        $migrationsDir = $this->environment->getBaseDirectory() . '/src/Entity/Migration';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($migrationsDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $migrationFiles = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            // Skip dotfiles
            $fileName = $file->getBasename('.php');
            if ($fileName == $file->getBasename()) {
                continue;
            }

            $className = 'App\\Entity\\Migration\\' . $fileName;
            $migrationFiles[$fileName] = $className;
        }

        $migrationFiles = array_reverse($migrationFiles);

        /** @var class-string $migrationClassName */
        foreach ($migrationFiles as $migrationClassName) {
            $reflClass = new ReflectionClass($migrationClassName);
            $reflAttrs = $reflClass->getAttributes(StableMigration::class);

            foreach ($reflAttrs as $reflAttrInfo) {
                /** @var StableMigration $reflAttr */
                $reflAttr = $reflAttrInfo->newInstance();

                if ($version === $reflAttr->version) {
                    return $migrationClassName;
                }
            }
        }

        throw new InvalidArgumentException(
            'No migration found for the specified version. Make sure to specify a version after 0.17.0.'
        );
    }
}
