<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\SettingsAwareTrait;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Nginx\ConfigWriter;
use App\Nginx\Nginx;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

final class RotateLogsTask extends AbstractTask
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo,
        private readonly Nginx $nginx,
    ) {
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
        $settings = $this->readSettings();

        $copiesToKeep = $settings->getBackupKeepCopies();
        if ($copiesToKeep > 0) {
            $backupStorageId = (int)$settings->getBackupStorageLocation();

            if ($backupStorageId > 0) {
                $storageLocation = $this->storageLocationRepo->findByType(
                    StorageLocationTypes::Backup,
                    $backupStorageId
                );

                if ($storageLocation instanceof StorageLocation) {
                    $this->rotateBackupStorage($storageLocation, $copiesToKeep);
                }
            }
        }
    }

    private function rotateBackupStorage(
        StorageLocation $storageLocation,
        int $copiesToKeep
    ): void {
        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

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

    private function cleanUpIcecastLog(Station $station): void
    {
        $configPath = $station->getRadioConfigDir();

        $finder = new Finder();

        $finder
            ->files()
            ->in($configPath)
            ->name('icecast_*.log.*')
            ->date('before 1 month ago');

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            if ($filePath) {
                @unlink($filePath);
            }
        }
    }

    private function rotateHlsLog(Station $station): bool
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
