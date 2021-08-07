<?php

declare(strict_types=1);

namespace App\Console\Command\Backup;

use App\Console\Command\CommandAbstract;
use App\Console\Command\Traits;
use App\Entity;
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
        Entity\Repository\StorageLocationRepository $storageLocationRepo,
        ?string $path = '',
        bool $excludeMedia = false,
        ?int $storageLocationId = null
    ): int {
        $start_time = microtime(true);

        if (empty($path)) {
            $path = 'manual_backup_' . gmdate('Ymd_Hi') . '.zip';
        }

        $file_ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ('/' === $path[0]) {
            $tmpPath = $path;
            $storageLocation = null;
        } else {
            $tmpPath = tempnam(sys_get_temp_dir(), 'backup_') . '.' . $file_ext;

            if (null === $storageLocationId) {
                $io->error('You must specify a storage location when providing a relative path.');
                return 1;
            }

            $storageLocation = $storageLocationRepo->findByType(
                Entity\StorageLocation::TYPE_BACKUP,
                $storageLocationId
            );
            if (!($storageLocation instanceof Entity\StorageLocation)) {
                $io->error('Invalid storage location specified.');
                return 1;
            }
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

        // phpcs:disable Generic.Files.LineLength
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
        // phpcs:enable

        $files_to_backup[] = $path_db_dump;
        $io->newLine();

        // Include station media if specified.
        if ($includeMedia) {
            $stations = $em->createQuery(
                <<<'DQL'
                    SELECT s FROM App\Entity\Station s
                DQL
            )->execute();

            foreach ($stations as $station) {
                /** @var Entity\Station $station */

                $mediaAdapter = $station->getMediaStorageLocation();
                if ($mediaAdapter->isLocal()) {
                    $files_to_backup[] = $mediaAdapter->getPath();
                }
            }
        }

        // Compress backup files.
        $io->section(__('Creating backup archive...'));

        // Strip leading slashes from backup paths.
        $files_to_backup = array_map(
            static function (string $val) {
                if (str_starts_with($val, '/')) {
                    return substr($val, 1);
                }
                return $val;
            },
            $files_to_backup
        );

        switch ($file_ext) {
            case 'gz':
            case 'tgz':
                $this->passThruProcess(
                    $io,
                    array_merge(
                        [
                            'tar',
                            'zcvf',
                            $tmpPath,
                        ],
                        $files_to_backup
                    ),
                    '/'
                );
                break;

            case 'zip':
            default:
                $dont_compress = ['.tar.gz', '.zip', '.jpg', '.mp3', '.ogg', '.flac', '.aac', '.wav'];

                $this->passThruProcess(
                    $io,
                    array_merge(
                        [
                            'zip',
                            '-r',
                            '-n',
                            implode(':', $dont_compress),
                            $tmpPath,
                        ],
                        $files_to_backup
                    ),
                    '/'
                );
                break;
        }

        if (null !== $storageLocation) {
            $fs = $storageLocation->getFilesystem();
            $fs->uploadAndDeleteOriginal($tmpPath, $path);
        }

        $io->newLine();

        // Cleanup
        $io->section(__('Cleaning up temporary files...'));

        Utilities\File::rmdirRecursive($tmp_dir_mariadb);

        $io->newLine();

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $io->success(
            [
                __('Backup complete in %.2f seconds.', $time_diff),
            ]
        );
        return 0;
    }
}
