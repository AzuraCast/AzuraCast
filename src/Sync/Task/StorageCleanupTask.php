<?php
namespace App\Sync\Task;

use App\Entity;
use App\Flysystem\Filesystem;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\Log\LoggerInterface;

class StorageCleanupTask extends AbstractTask
{
    protected Filesystem $filesystem;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Filesystem $filesystem
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->filesystem = $filesystem;
    }

    public function run(bool $force = false): void
    {
        // Check all stations for automation settings.
        // Use this to avoid detached entity errors.
        $stations = SimpleBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(/** @lang DQL */ 'SELECT s FROM App\Entity\Station s'),
            1
        );

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $this->runStation($station);
        }
    }

    protected function runStation(Entity\Station $station): void
    {
        $fs = $this->filesystem->getForStation($station, false);

        $allUniqueIdsRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT sm.unique_id FROM App\Entity\StationMedia sm WHERE sm.station = :station')
            ->setParameter('station', $station)
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
            'albumart' => Filesystem::PREFIX_ALBUM_ART,
            'waveform' => Filesystem::PREFIX_WAVEFORMS,
        ];

        foreach ($cleanupDirs as $key => $prefix) {
            $dirBase = $prefix . '://';
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
