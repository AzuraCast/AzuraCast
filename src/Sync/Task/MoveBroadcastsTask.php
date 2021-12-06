<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class MoveBroadcastsTask extends AbstractTask
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        protected Entity\Repository\StationStreamerBroadcastRepository $broadcastRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStorageLocations(Entity\StorageLocation::TYPE_STATION_RECORDINGS) as $storageLocation) {
            $this->processForStorageLocation($storageLocation);
        }
    }

    protected function processForStorageLocation(Entity\StorageLocation $storageLocation): void
    {
        if ($storageLocation->isStorageFull()) {
            $this->logger->error('Storage location is full; skipping broadcasts.', [
                'storageLocation' => (string)$storageLocation,
            ]);
            return;
        }

        $fs = $storageLocation->getFilesystem();

        $stations = $this->storageLocationRepo->getStationsUsingLocation($storageLocation);
        foreach ($stations as $station) {
            $finder = (new Finder())
                ->files()
                ->in($station->getRadioTempDir())
                ->name(Entity\StationStreamerBroadcast::PATH_PREFIX . '_*')
                ->notName('*.tmp')
                ->depth(1);

            $this->logger->debug('Files', ['files', iterator_to_array($finder)]);

            foreach ($finder as $file) {
                $this->logger->debug('File', ['file' => $file]);

                $recordingPath = $file->getRelativePathname();

                if (!$storageLocation->canHoldFile($file->getSize())) {
                    $this->logger->error(
                        'Storage location full; broadcast not moved to storage location. '
                        . 'Check temporary directory at path to recover file.',
                        [
                            'storageLocation' => (string)$storageLocation,
                            'path'            => $recordingPath,
                        ]
                    );
                    break;
                }

                $broadcast = $this->broadcastRepo->findByPath($station, $recordingPath);
                if (null !== $broadcast) {
                    $tempPath = $file->getPathname();
                    $fs->uploadAndDeleteOriginal($tempPath, $recordingPath);

                    $this->logger->info(
                        'Uploaded broadcast to storage location.',
                        [
                            'storageLocation' => (string)$storageLocation,
                            'path'            => $recordingPath,
                        ]
                    );
                } else {
                    @unlink($file->getPathname());

                    $this->logger->info(
                        'Could not find a corresponding broadcast.',
                        [
                            'path'            => $recordingPath,
                        ]
                    );
                }
            }
        }
    }
}
