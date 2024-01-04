<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Settings;
use App\Environment;
use App\Service\AzuraCastCentral;
use DateTimeInterface;
use GuzzleHttp\Exception\TransferException;

final class CheckUpdatesTask extends AbstractTask
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    // 3 hours + ~3 minutes to force irregularity in update checks.
    private const UPDATE_THRESHOLD = (60 * 60 * 3) + 150;

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
        $updateLastRun = $settings->getUpdateLastRun();

        return ($updateLastRun !== 0)
            ? $updateLastRun + self::UPDATE_THRESHOLD
            : $now->getTimestamp();
    }

    public function run(bool $force = false): void
    {
        $settings = $this->readSettings();

        $settings->updateUpdateLastRun();
        $this->writeSettings($settings);

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            if (!empty($updates)) {
                $settings->setUpdateResults($updates);
                $this->writeSettings($settings);

                $this->logger->info('Successfully checked for updates.', ['results' => $updates]);
            } else {
                $this->logger->error('Error parsing update data response from AzuraCast central.');
            }
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }
    }
}
