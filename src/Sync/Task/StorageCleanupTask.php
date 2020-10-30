<?php

namespace App\Sync\Task;

use App\Entity;
use App\Flysystem\FilesystemManager;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\Log\LoggerInterface;

class StorageCleanupTask extends AbstractTask
{
    protected FilesystemManager $filesystem;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        FilesystemManager $filesystem
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->filesystem = $filesystem;
    }

    public function run(bool $force = false): void
    {
        // Check all stations for automation settings.
        // Use this to avoid detached entity errors.
        $storageLocationsQuery = $this->em->createQuery(/** @lang DQL */ 'SELECT sl 
            FROM App\Entity\StorageLocation sl
            WHERE sl.type = :type')
            ->setParameter('type', Entity\StorageLocation::TYPE_STATION_MEDIA);

        $storageLocations = SimpleBatchIteratorAggregate::fromQuery($storageLocationsQuery, 1);
        foreach ($storageLocations as $storageLocation) {
            /** @var Entity\StorageLocation $storageLocation */
            $this->cleanMediaStorageLocation($storageLocation);
        }
    }

    protected function cleanMediaStorageLocation(Entity\StorageLocation $storageLocation): void
    {
        $fs = $storageLocation->getFilesystem();

        $allUniqueIdsRaw = $this->em
            ->createQuery(/** @lang DQL */ 'SELECT sm.unique_id 
                FROM App\Entity\StationMedia sm 
                WHERE sm.storage_location = :storageLocation')
            ->setParameter('storageLocation', $storageLocation)
            ->getArrayResult();

        $allUniqueIds = [];
        foreach ($allUniqueIdsRaw as $row) {
            $allUniqueIds[$row['unique_id']] = $row['unique_id'];
        }

        $removed = [
            'albumart' => 0,
            'waveform' => 0,
        ];

        $cleanupDirs = [
            'albumart' => Entity\StationMedia::DIR_ALBUM_ART,
            'waveform' => Entity\StationMedia::DIR_WAVEFORMS,
        ];

        foreach ($cleanupDirs as $key => $dirBase) {
            $dirContents = $fs->listContents($dirBase, true);

            foreach ($dirContents as $row) {
                if (!isset($allUniqueIds[$row['filename']])) {
                    $fs->delete($dirBase . $row['path']);
                    $removed[$key]++;
                }
            }
        }

        $this->logger->info('Storage directory cleanup completed.', $removed);
    }
}
