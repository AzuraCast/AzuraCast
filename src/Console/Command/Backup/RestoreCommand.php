<?php

declare(strict_types=1);

namespace App\Console\Command\Backup;

use App\Console\Command\CommandAbstract;
use App\Console\Command\Traits;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use const PATHINFO_EXTENSION;

class RestoreCommand extends CommandAbstract
{
    use Traits\PassThruProcess;

    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        EntityManagerInterface $em,
        string $path
    ): int {
        $start_time = microtime(true);

        $io->title('AzuraCast Restore');
        $io->writeln('Please wait while the backup is restored...');

        if ('/' !== $path[0]) {
            $path = '/var/azuracast/backups/' . $path;
        }

        if (!file_exists($path)) {
            $io->getErrorStyle()->error(__('Backup path %s not found!', $path));
            return 1;
        }

        // Extract tar.gz archive
        $io->section('Extracting backup file...');

        $file_ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        switch ($file_ext) {
            case 'gz':
            case 'tgz':
                $this->passThruProcess(
                    $io,
                    [
                        'tar',
                        'zxvf',
                        $path,
                    ],
                    '/'
                );
                break;

            case 'zip':
            default:
                $this->passThruProcess(
                    $io,
                    [
                        'unzip',
                        $path,
                    ],
                    '/'
                );
                break;
        }

        $io->newLine();

        // Handle DB dump
        $io->section('Importing database...');

        $tmp_dir_mariadb = '/tmp/azuracast_backup_mariadb';
        $path_db_dump = $tmp_dir_mariadb . '/db.sql';

        if (!file_exists($path_db_dump)) {
            $io->getErrorStyle()->error('Database backup file not found!');
            return 1;
        }

        $conn = $em->getConnection();
        $connParams = $conn->getParams();

        // Drop all preloaded tables prior to running a DB dump backup.
        $conn->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($conn->fetchFirstColumn('SHOW TABLES') as $table) {
            $conn->executeQuery('DROP TABLE IF EXISTS ' . $conn->quoteIdentifier($table));
        }
        $conn->executeQuery('SET FOREIGN_KEY_CHECKS = 1');

        $this->passThruProcess(
            $io,
            'mysql --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD $DB_DATABASE < $DB_DUMP',
            $tmp_dir_mariadb,
            [
                'DB_HOST' => $connParams['host'],
                'DB_DATABASE' => $conn->getDatabase(),
                'DB_USERNAME' => $connParams['user'],
                'DB_PASSWORD' => $connParams['password'],
                'DB_DUMP' => $path_db_dump,
            ]
        );

        Utilities\File::rmdirRecursive($tmp_dir_mariadb);
        $io->newLine();

        // Update from current version to latest.
        $io->section('Running standard updates...');

        $this->runCommand($output, 'azuracast:setup', ['--update' => true]);

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $io->success(
            [
                'Restore complete in ' . round($time_diff, 3) . ' seconds.',
            ]
        );
        return 0;
    }
}
