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
use Throwable;

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

        if (Path::isAbsolute($path)) {
            $tmpPath = $path;
            $storageLocation = null;
        } else {
            $tmpPath = $fsUtils->tempnam(
                sys_get_temp_dir(),
                'backup_',
                '.' . $fileExt
            );

            // Zip command cannot handle an existing file (even an empty one)
            @unlink($tmpPath);

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
        }

        $includeMedia = !$excludeMedia;
        $filesToBackup = [];

        $io->title(__('AzuraCast Backup'));
        $io->writeln(__('Please wait while a backup is generated...'));

        // Create temp directories
        $io->section(__('Creating temporary directories...'));

        $tmpDirMariadb = '/tmp/azuracast_backup_mariadb';
        try {
            $fsUtils->mkdir($tmpDirMariadb);
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->newLine();

        // Back up MariaDB
        $io->section(__('Backing up MariaDB...'));

        $pathDbDump = $tmpDirMariadb . '/db.sql';
        $this->dumpDatabase($io, $pathDbDump);

        $filesToBackup[] = $pathDbDump;
        $io->newLine();

        // Include station media if specified.
        if ($includeMedia) {
            $stations = $this->em->createQuery(
                <<<'DQL'
                    SELECT s FROM App\Entity\Station s
                DQL
            )->execute();

            /** @var Station $station */
            foreach ($stations as $station) {
                $mediaAdapter = $station->getMediaStorageLocation();
                if ($mediaAdapter->isLocal()) {
                    $filesToBackup[] = $mediaAdapter->getPath();
                }
            }
        }

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
                    $io,
                    array_merge(
                        [
                            'tar',
                            '-I',
                            'zstd',
                            '-cvf',
                            $tmpPath,
                        ],
                        $filesToBackup
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
                        $filesToBackup
                    ),
                    '/'
                );
                break;

            case 'zip':
            default:
                $dontCompress = ['.tar.gz', '.zip', '.jpg', '.mp3', '.ogg', '.flac', '.aac', '.wav'];

                $this->passThruProcess(
                    $io,
                    array_merge(
                        [
                            'zip',
                            '-r',
                            '-n',
                            implode(':', $dontCompress),
                            $tmpPath,
                        ],
                        $filesToBackup
                    ),
                    '/'
                );
                break;
        }

        if (null !== $storageLocation) {
            $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();
            $fs->uploadAndDeleteOriginal($tmpPath, $path);
        }

        $io->newLine();

        // Cleanup
        $io->section(__('Cleaning up temporary files...'));

        $fsUtils->remove($tmpDirMariadb);

        $io->newLine();

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
