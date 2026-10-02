<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Doctrine\Migrations\StableReleaseComparator;
use App\Utilities\Types;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use InvalidArgumentException;
use RuntimeException;
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
    public function __construct(
        private readonly StableReleaseComparator $comparator,
        private readonly ConfigurationLoader $migrationConfig,
    ) {
        parent::__construct();
    }

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
            $version = Types::string($input->getArgument('version'));
            $migrationVersion = $this->findMigration($version);
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return self::FAILURE;
        }

        $this->runCommand(
            $output,
            'migrations:sync-metadata-storage'
        );

        // Back up current DB state.
        try {
            $dbDumpPath = $this->saveOrRestoreDatabase($io);
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return self::FAILURE;
        }

        // Attempt DB rollback.
        $io->section(__('Reverting database migrations...'));

        try {
            // The database is still untouched at this point, so a failure here must not trigger a restore.
            try {
                $migrationsToRevert = $this->comparator->getMigrationsToRevert(
                    $this->getExecutedMigrations(),
                    $migrationVersion
                );
            } catch (Throwable $e) {
                $io->error(
                    sprintf(
                        __('Could not determine the migrations to revert: %s'),
                        $e->getMessage()
                    )
                );

                return self::FAILURE;
            }

            if ($migrationsToRevert === []) {
                $io->success(
                    sprintf(
                        __('No migrations to revert for rollback to stable version "%s".'),
                        $version
                    )
                );
                return self::SUCCESS;
            }

            $io->listing($migrationsToRevert);

            try {
                $exitCode = $this->runCommand(
                    $output,
                    'migrations:execute',
                    [
                        'versions' => $migrationsToRevert,
                        '--down' => true,
                    ]
                );

                if ($exitCode !== self::SUCCESS) {
                    throw new RuntimeException(
                        sprintf('Reverting the migrations failed with exit code %d.', $exitCode)
                    );
                }
            } catch (Throwable $e) {
                // Rollback to the DB dump from earlier.
                $io->error(
                    sprintf(
                        __('Database rollback failed: %s'),
                        $e->getMessage()
                    )
                );

                $this->tryEmergencyRestore($io, $dbDumpPath);

                return self::FAILURE;
            }
        } finally {
            new Filesystem()->remove($dbDumpPath);
        }

        $io->newLine();
        $io->success(
            sprintf(
                __('Database rolled back to stable release version "%s".'),
                $version
            )
        );

        return self::SUCCESS;
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

        return $this->comparator->getStableMigration($version)
            ?? throw new InvalidArgumentException(
                'No migration found for the specified version. Make sure to specify a version after 0.17.0.'
            );
    }

    /**
     * @return list<string>
     */
    private function getExecutedMigrations(): array
    {
        $metadataStorage = $this->migrationConfig->getConfiguration()->getMetadataStorageConfiguration();
        if (!$metadataStorage instanceof TableMetadataStorageConfiguration) {
            throw new RuntimeException('Invalid migration metadata storage.');
        }

        $conn = $this->em->getConnection();

        $executedMigrations = $conn->fetchFirstColumn(
            sprintf(
                'SELECT %s FROM %s',
                $metadataStorage->getVersionColumnName(),
                $metadataStorage->getTableName()
            )
        );

        return array_map(Types::string(...), $executedMigrations);
    }
}
