<?php

namespace App\Sync\Task;

use App\Entity;
use App\Environment;
use App\Service\AzuraCastCentral;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

class CheckUpdatesTask extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 3780;

    protected AzuraCastCentral $azuracastCentral;

    protected Entity\Repository\SettingsTableRepository $settingsTableRepo;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Settings $settings,
        Entity\Repository\SettingsTableRepository $settingsTableRepo,
        AzuraCastCentral $azuracastCentral
    ) {
        parent::__construct($em, $logger, $settings);

        $this->settingsTableRepo = $settingsTableRepo;
        $this->azuracastCentral = $azuracastCentral;
    }

    public function run(bool $force = false): void
    {
        if (!$force) {
            $update_last_run = $this->settings->getUpdateLastRun();

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
                $this->settings->setUpdateResults($updates);
                $this->settingsTableRepo->writeSettings($this->settings);

                $this->logger->info('Successfully checked for updates.', ['results' => $updates]);
            } else {
                $this->logger->error('Error parsing update data response from AzuraCast central.');
            }
        } catch (TransferException $e) {
            $this->logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }

        $this->settings->updateUpdateLastRun();
        $this->settingsTableRepo->writeSettings($this->settings);
    }
}
