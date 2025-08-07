<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Console\Application;
use App\Container\SettingsAwareTrait;
use App\Entity\StationSchedule;
use App\Message;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use Symfony\Component\Messenger\MessageBus;

final class RunBackupTask extends AbstractTask
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly MessageBus $messageBus,
        private readonly Application $console,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\BackupMessage) {
            $settings = $this->readSettings();
            $settings->updateBackupLastRun();

            $this->writeSettings($settings);

            [$resultCode, $resultOutput] = $this->runBackup(
                $message->path,
                $message->excludeMedia,
                $message->outputPath,
                $message->storageLocationId
            );

            $resultOutput = 'Exited with code ' . $resultCode . ":\n" . $resultOutput;

            $settings = $this->readSettings();
            $settings->backup_last_output = $resultOutput;
            $this->writeSettings($settings);
        }
    }

    /**
     * @param string|null $path
     * @param bool $excludeMedia
     * @param string|null $outputPath
     * @param int|null $storageLocationId
     *
     * @return mixed[] [int $result_code, string|false $result_output]
     */
    public function runBackup(
        ?string $path = null,
        bool $excludeMedia = false,
        ?string $outputPath = null,
        ?int $storageLocationId = null
    ): array {
        $inputParams = [];
        if (null !== $path) {
            $inputParams['path'] = $path;
        }
        if (null !== $storageLocationId) {
            $inputParams['--storage-location-id'] = $storageLocationId;
        }
        if ($excludeMedia) {
            $inputParams['--exclude-media'] = true;
        }

        return $this->console->runCommandWithArgs(
            'azuracast:backup',
            $inputParams,
            $outputPath ?? 'php://temp'
        );
    }

    public function run(bool $force = false): void
    {
        $settings = $this->readSettings();
        if (!$settings->backup_enabled) {
            $this->logger->debug('Automated backups disabled; skipping...');
            return;
        }

        $utc = Time::getUtc();
        $nowUtc = Time::nowUtc();

        $threshold = $nowUtc->subDay()->getTimestamp();
        $lastRun = $settings->backup_last_run;

        if ($lastRun <= $threshold) {
            // Check if the backup time matches (if it's set).
            $backupTimecode = $settings->backup_time_code;

            if (null !== $backupTimecode && '' !== $backupTimecode) {
                $isWithinTimecode = false;
                $backupDt = StationSchedule::getDateTime($backupTimecode, $utc, $nowUtc);

                /** @var CarbonImmutable[] $backupTimesToCheck */
                $backupTimesToCheck = [
                    $backupDt->subDay(),
                    $backupDt,
                ];

                foreach ($backupTimesToCheck as $backupStart) {
                    $backupEnd = $backupStart->addMinutes(15);

                    if ($nowUtc->between($backupStart, $backupEnd)) {
                        $isWithinTimecode = true;
                        break;
                    }
                }

                if (!$isWithinTimecode) {
                    return;
                }
            }

            // Trigger a new backup.
            $storageLocationId = $settings->backup_storage_location ?? 0;
            if ($storageLocationId <= 0) {
                $storageLocationId = null;
            }

            $pathExt = $settings->backup_format ?? 'zip';

            $message = new Message\BackupMessage();
            $message->storageLocationId = $storageLocationId;
            $message->path = 'automatic_backup_' . $nowUtc->format('Ymd_His') . '.' . $pathExt;
            $message->excludeMedia = $settings->backup_exclude_media;

            $this->messageBus->dispatch($message);
        }
    }
}
