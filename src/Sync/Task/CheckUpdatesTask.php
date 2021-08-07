<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Environment;
use App\Service\AzuraCastCentral;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

class CheckUpdatesTask extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 3780;

    public function __construct(
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected AzuraCastCentral $azuracastCentral,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        $settings = $this->settingsRepo->readSettings();

        if (!$force) {
            $update_last_run = $settings->getUpdateLastRun();

            if ($update_last_run > (time() - self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        if (Environment::getInstance()->isTesting()) {
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
        $this->settingsRepo->writeSettings($settings);
    }
}
