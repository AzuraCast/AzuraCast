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

class RunBackupTask extends AbstractTask
{
    public function __construct(
        protected MessageBus $messageBus,
        protected Application $console,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
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

            $settings = $this->settingsRepo->readSettings();
            $settings->setBackupLastResult($result_code);
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

        $backup_enabled = $settings->getBackupEnabled();
        if (!$backup_enabled) {
            $this->logger->debug('Automated backups disabled; skipping...');
            return;
        }

        $now_utc = CarbonImmutable::now('UTC');

        $threshold = $now_utc->subDay()->getTimestamp();
        $last_run = $settings->getBackupLastRun();

        if ($last_run <= $threshold) {
            // Check if the backup time matches (if it's set).
            $backupTimecode = $settings->getBackupTimeCode();

            if (null !== $backupTimecode && '' !== $backupTimecode) {
                $isWithinTimecode = false;
                $backupDt = Entity\StationSchedule::getDateTime($backupTimecode, $now_utc);

                /** @var CarbonInterface[] $backupTimesToCheck */
                $backupTimesToCheck = [
                    $backupDt->subDay(),
                    $backupDt,
                ];

                foreach ($backupTimesToCheck as $backupStart) {
                    $backupEnd = $backupStart->addMinutes(15);

                    if ($now_utc->between($backupStart, $backupEnd)) {
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

            $message = new Message\BackupMessage();
            $message->storageLocationId = $storageLocationId;
            $message->path = 'automatic_backup_' . gmdate('Ymd_His') . '.zip';
            $message->excludeMedia = $settings->getBackupExcludeMedia();

            $this->messageBus->dispatch($message);
        }
    }
}
