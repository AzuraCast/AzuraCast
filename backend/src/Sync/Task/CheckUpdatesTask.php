<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Settings;
use App\Environment;
use App\Service\AzuraCastCentral;
use DateTimeInterface;
use Throwable;

final class CheckUpdatesTask extends AbstractTask
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    // 13 hours + ~3 minutes to force irregularity in update checks.
    private const int|float UPDATE_THRESHOLD = (60 * 60 * 13) + 150;

    public function __construct(
        private readonly AzuraCastCentral $azuracastCentral
    ) {
    }

    public static function isDue(
        DateTimeInterface $now,
        Environment $environment,
        Settings $settings
    ): bool {
        if ($environment->isTesting()) {
            return false;
        }

        $nextRun = self::getNextRun($now, $environment, $settings);
        return $now->getTimestamp() > $nextRun;
    }

    public static function getNextRun(
        DateTimeInterface $now,
        Environment $environment,
        Settings $settings
    ): int {
        $updateLastRun = $settings->update_last_run;

        return ($updateLastRun !== 0)
            ? $updateLastRun + self::UPDATE_THRESHOLD
            : $now->getTimestamp();
    }

    public function run(bool $force = false): void
    {
        $settings = $this->readSettings();

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            $settings->update_results = $updates;
            $this->writeSettings($settings);

            $this->logger->info('Successfully checked for updates.', ['results' => $updates]);
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Error checking updates (%d): %s',
                    $e->getCode(),
                    $e->getMessage()
                ),
                [
                    'exception' => $e,
                ]
            );

            // Force re-setting of update data (to update timestamps).
            $updates = $settings->update_results;
            $settings->update_results = $updates;
            $this->writeSettings($settings);
        }
    }
}
