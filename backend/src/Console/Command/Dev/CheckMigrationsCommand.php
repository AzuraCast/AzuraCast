<?php

declare(strict_types=1);

namespace App\Console\Command\Dev;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Doctrine\Migrations\StableReleaseComparator;
use App\Utilities\Types;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'azuracast:dev:check-migrations',
    description: 'Check that DB migrations added since the given git ref sort after the latest stable release.',
)]
final class CheckMigrationsCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly StableReleaseComparator $migrationComparator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'base',
            InputArgument::REQUIRED,
            'The git ref to compare HEAD against, i.e. "main".'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $base = Types::string($input->getArgument('base'));

        $latestVersion = $this->migrationComparator->getLatestStableVersion();
        $latestMarker = ($latestVersion !== null)
            ? $this->migrationComparator->getStableMigration($latestVersion)
            : null;

        if ($latestVersion === null || $latestMarker === null) {
            $io->error('No migration with a #[StableMigration] marker found.');

            return self::FAILURE;
        }

        try {
            $addedMigrations = $this->getAddedMigrations($base);
        } catch (ProcessFailedException $exception) {
            $io->error(trim($exception->getProcess()->getErrorOutput()));

            return self::FAILURE;
        }

        $errors = [];
        foreach ($addedMigrations as $migration) {
            if ($this->migrationComparator->compare(new Version($migration), new Version($latestMarker)) < 0) {
                $errors[] = sprintf(
                    '%s sorts before %s, the marker of stable version %s.',
                    $migration,
                    $latestMarker,
                    $latestVersion
                );
            }
        }

        if ($errors !== []) {
            $errors[] = sprintf(
                'Add #[AfterStableRelease(\'%s\')] to these migrations, as they were not part of that release.',
                $latestVersion
            );

            $io->error($errors);

            return self::FAILURE;
        }

        $io->success(
            sprintf(
                '%d added migration(s) sort after the marker of stable version %s.',
                count($addedMigrations),
                $latestVersion
            )
        );

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function getAddedMigrations(string $base): array
    {
        $process = new Process(
            ['git', 'diff', '--name-only', '--relative', '--diff-filter=A', $base, 'HEAD'],
            $this->environment->getBackendDirectory() . '/src/Entity/Migration'
        );
        $process->mustRun();

        $migrations = [];
        foreach (explode("\n", trim($process->getOutput())) as $file) {
            if (str_starts_with($file, 'Version') && str_ends_with($file, '.php')) {
                $migrations[] = StableReleaseComparator::APP_MIGRATIONS_NAMESPACE . basename($file, '.php');
            }
        }

        return $migrations;
    }
}
