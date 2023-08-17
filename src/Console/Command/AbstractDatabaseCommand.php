<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Console\Command\Traits\PassThruProcess;
use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractDatabaseCommand extends CommandAbstract
{
    use PassThruProcess;
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;

    protected function getDatabaseSettingsAsCliFlags(): array
    {
        $connSettings = $this->environment->getDatabaseSettings();

        $commandEnvVars = [
            'DB_DATABASE' => $connSettings['dbname'],
            'DB_USERNAME' => $connSettings['user'],
            'DB_PASSWORD' => $connSettings['password'],
        ];

        $commandFlags = [
            '--user=$DB_USERNAME',
            '--password=$DB_PASSWORD',
        ];

        if (isset($connSettings['unix_socket'])) {
            $commandFlags[] = '--socket=$DB_SOCKET';
            $commandEnvVars['DB_SOCKET'] = $connSettings['unix_socket'];
        } else {
            $commandFlags[] = '--host=$DB_HOST';
            $commandFlags[] = '--port=$DB_PORT';
            $commandEnvVars['DB_HOST'] = $connSettings['host'];
            $commandEnvVars['DB_PORT'] = $connSettings['port'];
        }

        return [$commandFlags, $commandEnvVars];
    }

    protected function dumpDatabase(
        SymfonyStyle $io,
        string $path
    ): void {
        [$commandFlags, $commandEnvVars] = $this->getDatabaseSettingsAsCliFlags();

        $commandFlags[] = '--add-drop-table';
        $commandFlags[] = '--default-character-set=UTF8MB4';

        $commandEnvVars['DB_DEST'] = $path;

        $this->passThruProcess(
            $io,
            'mysqldump ' . implode(' ', $commandFlags) . ' $DB_DATABASE > $DB_DEST',
            dirname($path),
            $commandEnvVars
        );
    }

    protected function restoreDatabaseDump(
        SymfonyStyle $io,
        string $path
    ): void {
        if (!file_exists($path)) {
            throw new RuntimeException('Database backup file not found!');
        }

        $conn = $this->em->getConnection();

        // Drop all preloaded tables prior to running a DB dump backup.
        $conn->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($conn->fetchFirstColumn('SHOW TABLES') as $table) {
            $conn->executeQuery('DROP TABLE IF EXISTS ' . $conn->quoteIdentifier($table));
        }
        $conn->executeQuery('SET FOREIGN_KEY_CHECKS = 1');

        [$commandFlags, $commandEnvVars] = $this->getDatabaseSettingsAsCliFlags();

        $commandEnvVars['DB_DUMP'] = $path;

        $this->passThruProcess(
            $io,
            'mysql ' . implode(' ', $commandFlags) . ' $DB_DATABASE < $DB_DUMP',
            dirname($path),
            $commandEnvVars
        );
    }
}
