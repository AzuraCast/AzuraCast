<?php

namespace App\Sync\Task;

use App\Entity;
use App\Radio\Adapters;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use studio24\Rotate;
use Supervisor\Supervisor;
use Symfony\Component\Finder\Finder;

class RotateLogs extends AbstractTask
{
    protected Settings $appSettings;

    protected Adapters $adapters;

    protected Supervisor $supervisor;

    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Settings $appSettings,
        Adapters $adapters,
        Supervisor $supervisor,
        Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->appSettings = $appSettings;
        $this->adapters = $adapters;
        $this->supervisor = $supervisor;
        $this->storageLocationRepo = $storageLocationRepo;
    }

    public function run(bool $force = false): void
    {
        // Rotate logs for individual stations.
        $station_repo = $this->em->getRepository(Entity\Station::class);

        $stations = $station_repo->findAll();
        if (!empty($stations)) {
            foreach ($stations as $station) {
                /** @var Entity\Station $station */
                $this->logger->info(
                    'Processing logs for station.',
                    ['id' => $station->getId(), 'name' => $station->getName()]
                );

                $this->rotateStationLogs($station);
            }
        }

        // Rotate the main AzuraCast log.
        $rotate = new Rotate\Rotate($this->appSettings->getTempDirectory() . '/app.log');
        $rotate->keep(5);
        $rotate->size('5MB');
        $rotate->run();

        // Rotate the automated backups.
        $backups_to_keep = (int)$this->settingsRepo->getSetting(Entity\Settings::BACKUP_KEEP_COPIES, 0);

        if ($backups_to_keep > 0) {
            $backupStorageId = (int)$this->settingsRepo->getSetting(
                Entity\Settings::BACKUP_STORAGE_LOCATION,
                null
            );

            if ($backupStorageId > 0) {
                $storageLocation = $this->storageLocationRepo->findByType(
                    Entity\StorageLocation::TYPE_BACKUP,
                    $backupStorageId
                );

                if ($storageLocation instanceof Entity\StorageLocation && $storageLocation->isLocal()) {
                    $fs = $storageLocation->getFilesystem();
                    $autoBackupPath = $fs->getFullPath('automatic_backup.zip');

                    $rotate = new Rotate\Rotate($autoBackupPath);
                    $rotate->keep($backups_to_keep);
                    $rotate->run();
                }
            }
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
            @unlink($file_path);
        }
    }
}
