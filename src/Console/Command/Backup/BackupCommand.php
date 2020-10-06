<?php
namespace App\Console\Command\Backup;

use App\Console\Command\CommandAbstract;
use App\Console\Command\Traits;
use App\Entity;
use App\Sync\Task\Backup;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use const PATHINFO_EXTENSION;

class BackupCommand extends CommandAbstract
{
    use Traits\PassThruProcess;

    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        ?string $path = '',
        bool $excludeMedia = false
    ) {
        $start_time = microtime(true);

        if (empty($path)) {
            $path = 'manual_backup_' . gmdate('Ymd_Hi') . '.zip';
        }
        if ('/' !== $path[0]) {
            $path = Backup::BASE_DIR . '/' . $path;
        }

        $includeMedia = !$excludeMedia;
        $files_to_backup = [];

        $io->title(__('AzuraCast Backup'));
        $io->writeln(__('Please wait while a backup is generated...'));

        // Create temp directories
        $io->section(__('Creating temporary directories...'));

        $tmp_dir_mariadb = '/tmp/azuracast_backup_mariadb';
        if (!mkdir($tmp_dir_mariadb) && !is_dir($tmp_dir_mariadb)) {
            $io->error(__('Directory "%s" was not created', $tmp_dir_mariadb));
            return 1;
        }

        $io->newLine();

        // Back up MariaDB
        $io->section(__('Backing up MariaDB...'));

        $path_db_dump = $tmp_dir_mariadb . '/db.sql';

        $conn = $em->getConnection();
        $connParams = $conn->getParams();

        $this->passThruProcess(
            $io,
            'mysqldump --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD --add-drop-table --default-character-set=UTF8MB4 $DB_DATABASE > $DB_DEST',
            $tmp_dir_mariadb,
            [
                'DB_HOST' => $connParams['host'],
                'DB_DATABASE' => $conn->getDatabase(),
                'DB_USERNAME' => $connParams['user'],
                'DB_PASSWORD' => $connParams['password'],
                'DB_DEST' => $path_db_dump,
            ]
        );

        $files_to_backup[] = $path_db_dump;
        $io->newLine();

        // Include station media if specified.
        if ($includeMedia) {
            $stations = $em->createQuery(/** @lang DQL */ 'SELECT s FROM App\Entity\Station s')
                ->execute();

            foreach ($stations as $station) {
                /** @var Entity\Station $station */

                $media_dir = $station->getRadioMediaDir();
                if (!in_array($media_dir, $files_to_backup, true)) {
                    $files_to_backup[] = $media_dir;
                }

                $art_dir = $station->getRadioAlbumArtDir();
                if (!in_array($art_dir, $files_to_backup, true)) {
                    $files_to_backup[] = $art_dir;
                }
            }
        }

        // Compress backup files.
        $io->section(__('Creating backup archive...'));

        // Strip leading slashes from backup paths.
        $files_to_backup = array_map(function ($val) {
            if (0 === strpos($val, '/')) {
                return substr($val, 1);
            }
            return $val;
        }, $files_to_backup);

        $file_ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        switch ($file_ext) {
            case 'gz':
            case 'tgz':
                $this->passThruProcess($io, array_merge([
                    'tar',
                    'zcvf',
                    $path,
                ], $files_to_backup), '/');
                break;

            case 'zip':
            default:
                $dont_compress = ['.tar.gz', '.zip', '.jpg', '.mp3', '.ogg', '.flac', '.aac', '.wav'];

                $this->passThruProcess($io, array_merge([
                    'zip',
                    '-r',
                    '-n',
                    implode(':', $dont_compress),
                    $path,
                ], $files_to_backup), '/');
                break;
        }

        $io->newLine();

        // Cleanup
        $io->section(__('Cleaning up temporary files...'));

        Utilities::rmdirRecursive($tmp_dir_mariadb);

        $io->newLine();

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $io->success([
            __('Backup complete in %.2f seconds.', $time_diff),
        ]);
        return 0;
    }
}
