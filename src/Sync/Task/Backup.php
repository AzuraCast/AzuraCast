<?php

namespace App\Sync\Task;

use App\Console\Application;
use App\Entity;
use App\Message;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBus;

class Backup extends AbstractTask
{
    public const BASE_DIR = '/var/azuracast/backups';

    protected MessageBus $messageBus;

    protected Application $console;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        MessageBus $messageBus,
        Application $console
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->messageBus = $messageBus;
        $this->console = $console;
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\BackupMessage) {
            $this->settingsRepo->setSetting(Entity\Settings::BACKUP_LAST_RUN, time());

            [$result_code, $result_output] = $this->runBackup(
                $message->path,
                $message->excludeMedia,
                $message->outputPath,
                $message->storageLocationId
            );

            $this->settingsRepo->setSetting(Entity\Settings::BACKUP_LAST_RESULT, $result_code);
            $this->settingsRepo->setSetting(Entity\Settings::BACKUP_LAST_OUTPUT, $result_output);
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
        $backup_enabled = (bool)$this->settingsRepo->getSetting(Entity\Settings::BACKUP_ENABLED, false);
        if (!$backup_enabled) {
            $this->logger->debug('Automated backups disabled; skipping...');
            return;
        }

        $now_utc = CarbonImmutable::now('UTC');

        $threshold = $now_utc->subDay()->getTimestamp();
        $last_run = $this->settingsRepo->getSetting(Entity\Settings::BACKUP_LAST_RUN, 0);

        if ($last_run <= $threshold) {
            // Check if the backup time matches (if it's set).
            $backupTimecode = $this->settingsRepo->getSetting(Entity\Settings::BACKUP_TIME, null);

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
            $storageLocationId = (int)$this->settingsRepo->getSetting(
                Entity\Settings::BACKUP_STORAGE_LOCATION,
                0
            );
            if ($storageLocationId <= 0) {
                $storageLocationId = null;
            }

            $message = new Message\BackupMessage();
            $message->storageLocationId = $storageLocationId;
            $message->path = 'automatic_backup.zip';
            $message->excludeMedia = (bool)$this->settingsRepo->getSetting(Entity\Settings::BACKUP_EXCLUDE_MEDIA, 0);

            $this->messageBus->dispatch($message);
        }
    }
}
