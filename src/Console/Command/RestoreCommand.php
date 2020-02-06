<?php
namespace App\Console\Command;

use App\Sync\Task\Backup;
use App\Utilities;
use App\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use InfluxDB\Database;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use const PATHINFO_EXTENSION;

class RestoreCommand extends CommandAbstract
{
    use Traits\PassThruProcess;

    public function __invoke(
        SymfonyStyle $io,
        OutputInterface $output,
        EntityManager $em,
        Database $influxdb,
        string $path
    ) {
        $start_time = microtime(true);

        $io->title('AzuraCast Restore');
        $io->writeln('Please wait while the backup is restored...');

        if ('/' !== $path[0]) {
            $path = Backup::BASE_DIR . '/' . $path;
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
                $process = $this->passThruProcess($io, [
                    'tar',
                    'zxvf',
                    $path,
                ], '/');
                break;

            case 'zip':
            default:
                $process = $this->passThruProcess($io, [
                    'unzip',
                    $path,
                ], '/');
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

        $process = $this->passThruProcess(
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

        Utilities::rmdirRecursive($tmp_dir_mariadb);
        $io->newLine();

        // Handle InfluxDB import
        $tmp_dir_influxdb = '/tmp/azuracast_backup_influxdb';

        if (!is_dir($tmp_dir_influxdb)) {
            $io->getErrorStyle()->error('InfluxDB backup file not found!');
            return 1;
        }

        $influxdb_client = $influxdb->getClient();

        $process = $this->passThruProcess($io, [
            'influxd',
            'restore',
            '-portable',
            '-host',
            $influxdb_client->getHost() . ':8088',
            $tmp_dir_influxdb,
        ], $tmp_dir_influxdb);

        Utilities::rmdirRecursive($tmp_dir_influxdb);
        $io->newLine();

        // Update from current version to latest.
        $io->section('Running standard updates...');

        $this->runCommand($output, 'azuracast:setup', ['--update' => true]);

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $io->success([
            'Restore complete in ' . round($time_diff, 3) . ' seconds.',
        ]);
        return 0;
    }

    protected function passThruProcess(SymfonyStyle $io, $cmd, $cwd = null, array $env = []): Process
    {
        set_time_limit(3600);

        if (is_array($cmd)) {
            $process = new Process($cmd, $cwd);
        } else {
            $process = Process::fromShellCommandline($cmd, $cwd);
        }

        $process->setTimeout(3500);
        $process->setIdleTimeout(60);

        $stdout = [];
        $stderr = [];

        $process->mustRun(function ($type, $data) use ($process, $io, &$stdout, &$stderr) {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, $env);

        return $process;
    }
}
