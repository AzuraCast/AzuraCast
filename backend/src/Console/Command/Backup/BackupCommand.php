<?php

declare(strict_types=1);

namespace App\Console\Command\Backup;

use App\Console\Command\AbstractDatabaseCommand;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Utilities\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use const PATHINFO_EXTENSION;

#[AsCommand(
    name: 'azuracast:backup',
    description: 'Back up the AzuraCast database and statistics (and optionally media).',
)]
final class BackupCommand extends AbstractDatabaseCommand
{
    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct();
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

        $path = Types::stringOrNull($input->getArgument('path'), true)
            ?? 'manual_backup_' . gmdate('Ymd_Hi') . '.zip';

        $excludeMedia = Types::bool($input->getOption('exclude-media'));
        $storageLocationId = Types::intOrNull($input->getOption('storage-location-id'));

        $startTime = microtime(true);

        $fileExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $tmpPath = null;
        $tempFilesToCleanup = [];

        if (Path::isAbsolute($path)) {
            $destPath = $path;
            $storageLocation = null;
            $fs = null;
        } else {
            if (null === $storageLocationId) {
                $io->error('You must specify a storage location when providing a relative path.');
                return 1;
            }

            $storageLocation = $this->storageLocationRepo->findByType(
                StorageLocationTypes::Backup,
                $storageLocationId
            );

            if (!($storageLocation instanceof StorageLocation)) {
                $io->error('Invalid storage location specified.');
                return 1;
            }

            if ($storageLocation->isStorageFull()) {
                $io->error('Storage location is full.');
                return 1;
            }

            $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

            if ($storageLocation->adapter->isLocal()) {
                $destPath = $fs->getLocalPath($path);
            } else {
                $tmpPath = $fsUtils->tempnam(
                    sys_get_temp_dir(),
                    'backup_',
                    '.' . $fileExt
                );

                $destPath = $tmpPath;
                $tempFilesToCleanup[] = $tmpPath;
            }
        }

        // Zip command cannot handle an existing file (even an empty one)
        $fsUtils->remove($destPath);

        $filesToBackup = [];

        $io->title(__('AzuraCast Backup'));
        $io->writeln(__('Please wait while a backup is generated...'));

        // Backup uploaded custom assets
        $filesToBackup[] = $this->environment->getUploadsDirectory();

        // Include station media if specified.
        if (!$excludeMedia) {
            $stations = $this->em->createQuery(
                <<<'DQL'
                    SELECT s FROM App\Entity\Station s
                DQL
            )->execute();

            /** @var Station $station */
            foreach ($stations as $station) {
                foreach ($station->getAllStorageLocations() as $storageLocation) {
                    if ($storageLocation->adapter->isLocal()) {
                        $filesToBackup[] = $storageLocation->path;
                    }
                }
            }
        }

        try {
            // Back up MariaDB
            $io->section(__('Backing up MariaDB...'));

            $pathDbDump = self::DB_BACKUP_PATH;
            $tmpDirMariadb = dirname($pathDbDump);

            $fsUtils->mkdir($tmpDirMariadb);
            $this->dumpDatabase($io, $pathDbDump);

            $filesToBackup[] = $pathDbDump;

            $tempFilesToCleanup[] = $pathDbDump;
            $tempFilesToCleanup[] = $tmpDirMariadb;

            $io->newLine();

            // Compress backup files.
            $io->section(__('Creating backup archive...'));

            // Strip leading slashes from backup paths.
            $filesToBackup = array_map(
                static function (string $val) {
                    if (str_starts_with($val, '/')) {
                        return substr($val, 1);
                    }
                    return $val;
                },
                $filesToBackup
            );

            switch ($fileExt) {
                case 'tzst':
                    $this->passThruProcess(
                        $output,
                        array_merge(
                            [
                                'tar',
                                '-I',
                                'zstd',
                                '-cvf',
                                $destPath,
                            ],
                            $filesToBackup
                        ),
                        '/'
                    );
                    break;

                case 'gz':
                case 'tgz':
                    $this->passThruProcess(
                        $output,
                        array_merge(
                            [
                                'tar',
                                'zcvf',
                                $destPath,
                            ],
                            $filesToBackup
                        ),
                        '/'
                    );
                    break;

                case 'zip':
                default:
                    $dontCompress = ['.tar.gz', '.zip', '.jpg', '.mp3', '.ogg', '.flac', '.aac', '.wav'];

                    $this->passThruProcess(
                        $output,
                        array_merge(
                            [
                                'zip',
                                '-r',
                                '-n',
                                implode(':', $dontCompress),
                                $destPath,
                            ],
                            $filesToBackup
                        ),
                        '/'
                    );
                    break;
            }

            if (null !== $storageLocation) {
                $storageLocation->addStorageUsed(filesize($destPath) ?: 0);

                $this->em->persist($storageLocation);
                $this->em->flush();
            }

            if (null !== $fs && null !== $tmpPath) {
                $fs->uploadAndDeleteOriginal($tmpPath, $path);
            }

            $io->newLine();
        } finally {
            // Cleanup
            $io->section(__('Cleaning up temporary files...'));

            $fsUtils->remove($tempFilesToCleanup);

            $io->newLine();
        }

        $endTime = microtime(true);
        $timeDiff = $endTime - $startTime;

        $io->success(
            [
                sprintf(
                    __('Backup complete in %.2f seconds.'),
                    $timeDiff
                ),
            ]
        );
        return 0;
    }
}
