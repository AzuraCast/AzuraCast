<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Nginx\ConfigWriter;
use App\Nginx\Nginx;
use App\Radio\Adapters;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Supervisor\SupervisorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

class RotateLogsTask extends AbstractTask
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        protected Environment $environment,
        protected Adapters $adapters,
        protected SupervisorInterface $supervisor,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\Repository\StorageLocationRepository $storageLocationRepo,
        protected Nginx $nginx,
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return '34 * * * *';
    }

    public function run(bool $force = false): void
    {
        // Rotate logs for individual stations.
        $hlsRotated = false;

        foreach ($this->iterateStations() as $station) {
            $this->logger->info(
                'Rotating logs for station.',
                ['station' => (string)$station]
            );

            try {
                $this->cleanUpIcecastLog($station);

                if ($station->getEnableHls() && $this->rotateHlsLog($station)) {
                    $hlsRotated = true;
                }
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [
                    'station' => (string)$station,
                ]);
            }
        }

        if ($hlsRotated) {
            $this->nginx->reopenLogs();
        }

        // Rotate the automated backups.
        $settings = $this->settingsRepo->readSettings();

        $copiesToKeep = $settings->getBackupKeepCopies();
        if ($copiesToKeep > 0) {
            $backupStorageId = (int)$settings->getBackupStorageLocation();

            if ($backupStorageId > 0) {
                $storageLocation = $this->storageLocationRepo->findByType(
                    Entity\Enums\StorageLocationTypes::Backup,
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

    protected function rotateHlsLog(Entity\Station $station): bool
    {
        $hlsLogFile = ConfigWriter::getHlsLogFile($station);
        $hlsLogBackup = $hlsLogFile . '.1';

        if (!file_exists($hlsLogFile)) {
            return false;
        }

        $fsUtils = new Filesystem();

        if (file_exists($hlsLogBackup)) {
            $fsUtils->remove([$hlsLogBackup]);
        }

        $fsUtils->rename($hlsLogFile, $hlsLogBackup);
        return true;
    }
}
