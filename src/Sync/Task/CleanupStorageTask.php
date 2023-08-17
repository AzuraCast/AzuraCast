<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Flysystem\StationFilesystems;
use Exception;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Finder\Finder;
use Throwable;

final class CleanupStorageTask extends AbstractTask
{
    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '24 * * * *';
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            try {
                /** @var Station $station */
                $this->cleanStationTempFiles($station);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'station' => (string)$station,
                ]);
            }
        }

        $storageLocations = $this->iterateStorageLocations(StorageLocationTypes::StationMedia);
        foreach ($storageLocations as $storageLocation) {
            try {
                /** @var StorageLocation $storageLocation */
                $this->cleanMediaStorageLocation($storageLocation);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'storageLocation' => (string)$storageLocation,
                ]);
            }
        }
    }

    private function cleanStationTempFiles(Station $station): void
    {
        $tempDir = $station->getRadioTempDir();
        $finder = new Finder();

        $finder
            ->files()
            ->in($tempDir)
            ->date('before 2 days ago');

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if (false !== $filePath) {
                @unlink($filePath);
            }
        }
    }

    private function cleanMediaStorageLocation(StorageLocation $storageLocation): void
    {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $allUniqueIdsRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT sm.unique_id
                FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->getArrayResult();

        $allUniqueIds = [];
        foreach ($allUniqueIdsRaw as $row) {
            $allUniqueIds[$row['unique_id']] = $row['unique_id'];
        }

        if (0 === count($allUniqueIds)) {
            $this->logger->notice(
                sprintf('Skipping storage location %s: no media found.', $storageLocation)
            );
            return;
        }

        $removed = [
            'albumart' => 0,
            'waveform' => 0,
        ];

        $cleanupDirs = [
            'albumart' => StationFilesystems::DIR_ALBUM_ART,
            'waveform' => StationFilesystems::DIR_WAVEFORMS,
        ];

        foreach ($cleanupDirs as $key => $dirBase) {
            try {
                /** @var StorageAttributes $row */
                foreach ($fs->listContents($dirBase, true) as $row) {
                    $path = $row->path();


                    $filename = pathinfo($path, PATHINFO_FILENAME);
                    if (!isset($allUniqueIds[$filename])) {
                        $fs->delete($path);
                        $removed[$key]++;
                    }
                }
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf('Filesystem error: %s', $e->getMessage()),
                    [
                        'exception' => $e,
                    ]
                );
            }
        }

        $this->logger->info('Storage directory cleanup completed.', $removed);
    }
}
