<?php

namespace App\Sync\Task;

use App\Entity;
use App\Service\AzuraCastCentral;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

class CheckForUpdates extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 3780;

    protected AzuraCastCentral $azuracastCentral;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        AzuraCastCentral $azuracastCentral
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->azuracastCentral = $azuracastCentral;
    }

    public function run(bool $force = false): void
    {
        if (!$force) {
            $update_last_run = (int)$this->settingsRepo->getSetting(Entity\Settings::UPDATE_LAST_RUN, 0);

            if ($update_last_run > (time() - self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        if (Settings::getInstance()->isTesting()) {
            $this->logger->info('Update checks are currently disabled for this AzuraCast instance.');
            return;
        }

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            if (!empty($updates)) {
                $this->settingsRepo->setSetting(Entity\Settings::UPDATE_RESULTS, $updates);
                $this->logger->info('Successfully checked for updates.', ['results' => $updates]);
            } else {
                $this->logger->error('Error parsing update data response from AzuraCast central.');
            }
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }

        $this->settingsRepo->setSetting(Entity\Settings::UPDATE_LAST_RUN, time());
    }
}
