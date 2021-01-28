<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Radio\Adapters;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Log\LoggerInterface;
use Supervisor\Supervisor;
use Symfony\Component\Finder\Finder;

class RotateLogsTask extends AbstractTask
{
    protected Environment $environment;

    protected Adapters $adapters;

    protected Supervisor $supervisor;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Entity\Repository\StorageLocationRepository $storageLocationRepo;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        Environment $environment,
        Adapters $adapters,
        Supervisor $supervisor,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
        parent::__construct($em, $logger);

        $this->environment = $environment;
        $this->adapters = $adapters;
        $this->supervisor = $supervisor;

        $this->settingsRepo = $settingsRepo;
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

        $iterator = $fs->createIterator(
            '',
            [
                Options::OPTION_IS_RECURSIVE => false,
                Options::OPTION_FILTER => function (array $item): bool {
                    return (isset($item['path']) && 0 === stripos($item['path'], 'automatic_backup'));
                },
            ]
        );

        $backupsByTime = [];
        foreach ($iterator as $backup) {
            $backupsByTime[$backup['timestamp']] = $backup['path'];
        }

        if (count($backupsByTime) <= $copiesToKeep) {
            return;
        }

        krsort($backupsByTime);

        $backupsToDelete = array_slice($backupsByTime, $copiesToKeep);

        foreach ($backupsToDelete as $backupToDelete) {
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
            @unlink($file_path);
        }
    }
}
