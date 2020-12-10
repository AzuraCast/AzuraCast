<?php

namespace App\Sync\Task;

use App\Entity;
use App\Flysystem\FilesystemManager;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class CleanupStorageTask extends AbstractTask
{
    protected FilesystemManager $filesystem;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        FilesystemManager $filesystem
    ) {
        parent::__construct($em, $logger);

        $this->filesystem = $filesystem;
    }

    public function run(bool $force = false): void
    {
        $stationsQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT s FROM App\Entity\Station s
            DQL
        );

        $stations = SimpleBatchIteratorAggregate::fromQuery($stationsQuery, 1);
        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $this->cleanStationTempFiles($station);
        }

        // Check all stations for automation settings.
        // Use this to avoid detached entity errors.
        $storageLocationsQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT sl FROM App\Entity\StorageLocation sl
                WHERE sl.type = :type
            DQL
        )->setParameter('type', Entity\StorageLocation::TYPE_STATION_MEDIA);

        $storageLocations = SimpleBatchIteratorAggregate::fromQuery($storageLocationsQuery, 1);
        foreach ($storageLocations as $storageLocation) {
            /** @var Entity\StorageLocation $storageLocation */
            $this->cleanMediaStorageLocation($storageLocation);
        }
    }

    protected function cleanStationTempFiles(Entity\Station $station): void
    {
        $tempDir = $station->getRadioTempDir();
        $finder = new Finder();

        $finder
            ->files()
            ->in($tempDir)
            ->date('before 2 days ago');

        foreach ($finder as $file) {
            $file_path = $file->getRealPath();
            @unlink($file_path);
        }
    }

    protected function cleanMediaStorageLocation(Entity\StorageLocation $storageLocation): void
    {
        $fs = $storageLocation->getFilesystem();

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
                sprintf('Skipping storage location %s: no media found.', (string)$storageLocation)
            );
            return;
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
                    $fs->delete($row['path']);
                    $removed[$key]++;
                }
            }
        }

        $this->logger->info('Storage directory cleanup completed.', $removed);
    }
}
