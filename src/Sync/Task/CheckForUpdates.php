<?php
namespace App\Sync\Task;

use App\Entity;
use App\Service\AzuraCastCentral;
use App\Settings;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

class CheckForUpdates extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 3780;

    /** @var AzuraCastCentral */
    protected $azuracastCentral;

    /**
     * @param EntityManager $em
     * @param AzuraCastCentral $azuracastCentral
     */
    public function __construct(
        EntityManager $em,
        AzuraCastCentral $azuracastCentral
    ) {
        parent::__construct($em);

        $this->azuracastCentral = $azuracastCentral;
    }

    public function run($force = false): void
    {
        $logger = \Azura\Logger::getInstance();

        if (!$force) {
            $update_last_run = (int)$this->settingsRepo->getSetting(Entity\Settings::UPDATE_LAST_RUN, 0);

            if ($update_last_run > (time() - self::UPDATE_THRESHOLD)) {
                $logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        $check_for_updates = (int)$this->settingsRepo->getSetting(Entity\Settings::CENTRAL_UPDATES,
            Entity\Settings::UPDATES_RELEASE_ONLY);

        if (Entity\Settings::UPDATES_NONE === $check_for_updates || Settings::getInstance()->isTesting()) {
            $logger->info('Update checks are currently disabled for this AzuraCast instance.');
            return;
        }

        try {
            $updates = $this->azuracastCentral->checkForUpdates();

            if (!empty($updates)) {
                $this->settingsRepo->setSetting(Entity\Settings::UPDATE_RESULTS, $updates);
                $logger->info('Successfully checked for updates.', ['results' => $updates]);
            } else {
                $logger->error('Error parsing update data response from AzuraCast central.');
            }
        } catch (TransferException $e) {
            $logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }

        $this->settingsRepo->setSetting(Entity\Settings::UPDATE_LAST_RUN, time());
    }
}
