<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Service\AzuraCastCentral;
use GuzzleHttp\Exception\TransferException;

final class CheckUpdatesTask extends AbstractTask
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    private const UPDATE_THRESHOLD = 3780;

    public function __construct(
        private readonly AzuraCastCentral $azuracastCentral
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '3-59/5 * * * *';
    }

    public function run(bool $force = false): void
    {
        $settings = $this->readSettings();

        if (!$force) {
            $updateLastRun = $settings->getUpdateLastRun();

            if ($updateLastRun > (time() - self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        if ($this->environment->isTesting()) {
            $this->logger->info('Update checks are currently disabled for this AzuraCast instance.');
            return;
        }

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            if (!empty($updates)) {
                $settings->setUpdateResults($updates);

                $this->logger->info('Successfully checked for updates.', ['results' => $updates]);
            } else {
                $this->logger->error('Error parsing update data response from AzuraCast central.');
            }
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }

        $settings->updateUpdateLastRun();
        $this->writeSettings($settings);
    }
}
