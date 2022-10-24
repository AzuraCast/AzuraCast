<?php

declare(strict_types=1);

namespace App\Console\Command\Backup;

use App\Console\Command\AbstractDatabaseCommand;
use App\Entity;
use App\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Throwable;

use const PATHINFO_EXTENSION;

#[AsCommand(
    name: 'azuracast:backup',
    description: 'Back up the AzuraCast database and statistics (and optionally media).',
)]
final class BackupCommand extends AbstractDatabaseCommand
{
    public function __construct(
        Environment $environment,
        EntityManagerInterface $em,
        private readonly Entity\Repository\StorageLocationRepository $storageLocationRepo,
    ) {
        parent::__construct($environment, $em);
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED)
            ->addOption('storage-location-id', null, InputOption::VALUE_OPTIONAL)
            ->addOption('exclude-media', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fsUtils = new Filesystem();

        $path = $input->getArgument('path');
        $excludeMedia = (bool)$input->getOption('exclude-media');
        $storageLocationId = $input->getOption('storage-location-id');

        $start_time = microtime(true);

        if (empty($path)) {
            $path = 'manual_backup_' . gmdate('Ymd_Hi') . '.zip';
        }

        $file_ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (Path::isAbsolute($path)) {
            $tmpPath = $path;
            $storageLocation = null;
        } else {
            $tmpPath = $fsUtils->tempnam(
                sys_get_temp_dir(),
                'backup_',
                '.' . $file_ext
            );

            // Zip command cannot handle an existing file (even an empty one)
            @unlink($tmpPath);

            if (null === $storageLocationId) {
                $io->error('You must specify a storage location when providing a relative path.');
                return 1;
            }

            $storageLocation = $this->storageLocationRepo->findByType(
                Entity\Enums\StorageLocationTypes::Backup,
                $storageLocationId
            );
            if (!($storageLocation instanceof Entity\StorageLocation)) {
                $io->error('Invalid storage location specified.');
                return 1;
            }

            if ($storageLocation->isStorageFull()) {
                $io->error('Storage location is full.');
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
        try {
            $fsUtils->mkdir($tmp_dir_mariadb);
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->newLine();

        // Back up MariaDB
        $io->section(__('Backing up MariaDB...'));

        $path_db_dump = $tmp_dir_mariadb . '/db.sql';
        $this->dumpDatabase($io, $path_db_dump);

        $files_to_backup[] = $path_db_dump;
        $io->newLine();

        // Include station media if specified.
        if ($includeMedia) {
            $stations = $this->em->createQuery(
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
            case 'tzst':
                $this->passThruProcess(
                    $io,
                    array_merge(
                        [
                            'tar',
                            '-I',
                            'zstd',
                            '-cvf',
                            $tmpPath,
                        ],
                        $files_to_backup
                    ),
                    '/'
                );
                break;

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

        $fsUtils->remove($tmp_dir_mariadb);

        $io->newLine();

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $io->success(
            [
                sprintf(
                    __('Backup complete in %.2f seconds.'),
                    $time_diff
                ),
            ]
        );
        return 0;
    }
}
