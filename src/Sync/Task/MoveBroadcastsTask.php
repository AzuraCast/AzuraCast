<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StationStreamerBroadcastRepository;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\StationStreamerBroadcast;
use App\Entity\StorageLocation;
use Symfony\Component\Finder\Finder;
use Throwable;

final class MoveBroadcastsTask extends AbstractTask
{
    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    public function __construct(
        private readonly StationStreamerBroadcastRepository $broadcastRepo,
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function run(bool $force = false): void
    {
        foreach (
            $this->iterateStorageLocations(
                StorageLocationTypes::StationRecordings
            ) as $storageLocation
        ) {
            try {
                /** @var StorageLocation $storageLocation */
                $this->processForStorageLocation($storageLocation);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'storageLocation' => (string)$storageLocation,
                ]);
            }
        }
    }

    private function processForStorageLocation(StorageLocation $storageLocation): void
    {
        if ($storageLocation->isStorageFull()) {
            $this->logger->error('Storage location is full; skipping broadcasts.', [
                'storageLocation' => (string)$storageLocation,
            ]);
            return;
        }

        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $stations = $this->storageLocationRepo->getStationsUsingLocation($storageLocation);
        foreach ($stations as $station) {
            $finder = (new Finder())
                ->files()
                ->in($station->getRadioTempDir())
                ->name(StationStreamerBroadcast::PATH_PREFIX . '_*')
                ->notName('*.tmp')
                ->depth(1);

            $this->logger->debug('Files', ['files', iterator_to_array($finder, false)]);

            foreach ($finder as $file) {
                $this->logger->debug('File', ['file' => $file]);

                $recordingPath = $file->getRelativePathname();

                if (!$storageLocation->canHoldFile($file->getSize())) {
                    $this->logger->error(
                        'Storage location full; broadcast not moved to storage location. '
                        . 'Check temporary directory at path to recover file.',
                        [
                            'storageLocation' => (string)$storageLocation,
                            'path' => $recordingPath,
                        ]
                    );
                    break;
                }

                $broadcast = $this->broadcastRepo->getOrCreateFromPath($station, $recordingPath);
                if (null !== $broadcast) {
                    if (0 === $broadcast->getTimestampEnd()) {
                        $broadcast->setTimestampEnd($file->getMTime() ?: time());
                    }

                    $this->em->persist($broadcast);
                    $this->em->flush();

                    $tempPath = $file->getPathname();
                    $fs->uploadAndDeleteOriginal($tempPath, $recordingPath);

                    $this->logger->info(
                        'Uploaded broadcast to storage location.',
                        [
                            'storageLocation' => (string)$storageLocation,
                            'path' => $recordingPath,
                        ]
                    );
                } else {
                    @unlink($file->getPathname());

                    $this->logger->info(
                        'Could not find a corresponding broadcast.',
                        [
                            'path' => $recordingPath,
                        ]
                    );
                }
            }
        }
    }
}
