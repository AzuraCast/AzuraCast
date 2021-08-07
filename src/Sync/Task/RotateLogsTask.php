<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Radio\Adapters;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Supervisor\Supervisor;
use Symfony\Component\Finder\Finder;

class RotateLogsTask extends AbstractTask
{
    public function __construct(
        protected Environment $environment,
        protected Adapters $adapters,
        protected Supervisor $supervisor,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        // Rotate logs for individual stations.
        foreach ($this->iterateStations() as $station) {
            $this->logger->info(
                'Processing logs for station.',
                ['id' => $station->getId(), 'name' => $station->getName()]
            );

            $this->rotateStationLogs($station);
        }

        // Rotate the automated backups.
        $settings = $this->settingsRepo->readSettings();

        $copiesToKeep = $settings->getBackupKeepCopies();
        if ($copiesToKeep > 0) {
            $backupStorageId = (int)$settings->getBackupStorageLocation();

            if ($backupStorageId > 0) {
                $storageLocation = $this->storageLocationRepo->findByType(
                    Entity\StorageLocation::TYPE_BACKUP,
                    $backupStorageId
                );

                if ($storageLocation instanceof Entity\StorageLocation) {
                    $this->rotateBackupStorage($storageLocation, $copiesToKeep);
                }
            }
        }
    }

    protected function rotateBackupStorage(
        Entity\StorageLocation $storageLocation,
        int $copiesToKeep
    ): void {
        $fs = $storageLocation->getFilesystem();

        $iterator = $fs->listContents('', false)->filter(
            function (StorageAttributes $attrs) {
                return 0 === stripos($attrs->path(), 'automatic_backup');
            }
        );

        $backupsByTime = [];
        foreach ($iterator as $backup) {
            /** @var StorageAttributes $backup */
            $backupsByTime[$backup->lastModified()] = $backup->path();
        }

        if (count($backupsByTime) <= $copiesToKeep) {
            return;
        }

        krsort($backupsByTime);

        foreach (array_slice($backupsByTime, $copiesToKeep) as $backupToDelete) {
            $fs->delete($backupToDelete);
            $this->logger->info(sprintf('Deleted automated backup: "%s"', $backupToDelete));
        }
    }

    /**
     * Rotate logs that are not automatically rotated (currently Liquidsoap only).
     *
     * @param Entity\Station $station
     *
     */
    public function rotateStationLogs(Entity\Station $station): void
    {
        $this->cleanUpIcecastLog($station);
    }

    protected function cleanUpIcecastLog(Entity\Station $station): void
    {
        $config_path = $station->getRadioConfigDir();

        $finder = new Finder();

        $finder
            ->files()
            ->in($config_path)
            ->name('icecast_*.log.*')
            ->date('before 1 month ago');

        foreach ($finder as $file) {
            $file_path = $file->getRealPath();
            if ($file_path) {
                @unlink($file_path);
            }
        }
    }
}
