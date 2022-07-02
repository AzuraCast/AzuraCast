<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Console\Application;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Message;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

final class RunBackupTask extends AbstractTask
{
    public function __construct(
        private readonly MessageBus $messageBus,
        private readonly Application $console,
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
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
            $settings = $this->settingsRepo->readSettings();
            $settings->updateBackupLastRun();

            $this->settingsRepo->writeSettings($settings);

            [$result_code, $result_output] = $this->runBackup(
                $message->path,
                $message->excludeMedia,
                $message->outputPath,
                $message->storageLocationId
            );

            $result_output = 'Exited with code ' . $result_code . ":\n" . $result_output;

            $settings = $this->settingsRepo->readSettings();
            $settings->setBackupLastOutput($result_output);
            $this->settingsRepo->writeSettings($settings);
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
        $input_params = [];
        if (null !== $path) {
            $input_params['path'] = $path;
        }
        if (null !== $storageLocationId) {
            $input_params['--storage-location-id'] = $storageLocationId;
        }
        if ($excludeMedia) {
            $input_params['--exclude-media'] = true;
        }

        return $this->console->runCommandWithArgs(
            'azuracast:backup',
            $input_params,
            $outputPath ?? 'php://temp'
        );
    }

    public function run(bool $force = false): void
    {
        $settings = $this->settingsRepo->readSettings();

        if (!$settings->getBackupEnabled()) {
            $this->logger->debug('Automated backups disabled; skipping...');
            return;
        }

        $nowUtc = CarbonImmutable::now('UTC');

        $threshold = $nowUtc->subDay()->getTimestamp();
        $last_run = $settings->getBackupLastRun();

        if ($last_run <= $threshold) {
            // Check if the backup time matches (if it's set).
            $backupTimecode = $settings->getBackupTimeCode();

            if (null !== $backupTimecode && '' !== $backupTimecode) {
                $isWithinTimecode = false;
                $backupDt = Entity\StationSchedule::getDateTime($backupTimecode, $nowUtc);

                /** @var CarbonInterface[] $backupTimesToCheck */
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
            $storageLocationId = $settings->getBackupStorageLocation() ?? 0;
            if ($storageLocationId <= 0) {
                $storageLocationId = null;
            }

            $pathExt = $settings->getBackupFormat() ?? 'zip';

            $message = new Message\BackupMessage();
            $message->storageLocationId = $storageLocationId;
            $message->path = 'automatic_backup_' . $nowUtc->format('Ymd_His') . '.' . $pathExt;
            $message->excludeMedia = $settings->getBackupExcludeMedia();

            $this->messageBus->dispatch($message);
        }
    }
}
