<?php

declare(strict_types=1);

namespace App\Console\Command;

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
        try {
            $dbDumpPath = $this->saveOrRestoreDatabase($io);
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return 1;
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

            return $this->tryEmergencyRestore($io, $dbDumpPath);
        } finally {
            (new Filesystem())->remove($dbDumpPath);
        }

        $io->newLine();
        $io->success(
            __('Database migration completed!')
        );
        return 0;
    }
}
