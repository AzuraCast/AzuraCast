<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\StorageLocation;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'azuracast:setup:migrate',
    description: 'Migrate the database to the latest revision.',
)]
final class MigrateDbCommand extends AbstractDatabaseCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(__('Database Migrations'));

        $this->runCommand(
            $output,
            'migrations:sync-metadata-storage'
        );

        if (
            0 === $this->runCommand(
                new NullOutput(),
                'migrations:up-to-date'
            )
        ) {
            $io->success(__('Database is already up to date!'));
            return 0;
        }

        // Back up current DB state.
        $io->section(__('Backing up initial database state...'));

        $tempDir = StorageLocation::DEFAULT_BACKUPS_PATH;
        $dbDumpPath = $tempDir . '/pre_migration_db.sql';

        $fs = new Filesystem();

        if ($fs->exists($dbDumpPath)) {
            $io->info([
                __('We detected a database restore file from a previous (possibly failed) migration.'),
                __('Attempting to restore that now...'),
            ]);

            try {
                $this->restoreDatabaseDump($io, $dbDumpPath);
            } catch (Exception $e) {
                $io->error(
                    sprintf(
                        __('Restore failed: %s'),
                        $e->getMessage()
                    )
                );
                return 1;
            }
        } else {
            try {
                $this->dumpDatabase($io, $dbDumpPath);
            } catch (Exception $e) {
                $io->error(
                    sprintf(
                        __('Initial backup failed: %s'),
                        $e->getMessage()
                    )
                );
                return 1;
            }
        }

        // Attempt DB migration.
        $io->section(__('Running database migrations...'));

        try {
            $this->runCommand(
                $output,
                'migrations:migrate',
                [
                    '--allow-no-migration' => true,
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

            $io->section(__('Attempting to roll back to previous database state...'));

            try {
                $this->restoreDatabaseDump($io, $dbDumpPath);

                $io->warning([
                    __('Your database was restored due to a failed migration.'),
                    __('Please report this bug to our developers.'),
                ]);
                return 0;
            } catch (Exception $e) {
                $io->error(
                    sprintf(
                        __('Restore failed: %s'),
                        $e->getMessage()
                    )
                );
                return 1;
            }
        } finally {
            $fs->remove($dbDumpPath);
        }

        $io->newLine();
        $io->success(
            __('Database migration completed!')
        );
        return 0;
    }
}
