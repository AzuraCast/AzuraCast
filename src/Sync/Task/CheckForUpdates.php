<?php
namespace App\Sync\Task;

use App\Entity;
use App\Service\AzuraCastCentral;
use App\Version;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

class CheckForUpdates extends AbstractTask
{
    protected const UPDATE_THRESHOLD = 3780;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Settings */
    protected $app_settings;

    /** @var AzuraCastCentral */
    protected $azuracast_central;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param Settings $app_settings
     * @param AzuraCastCentral $azuracast_central
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        Settings $app_settings,
        AzuraCastCentral $azuracast_central
    ) {
        parent::__construct($em, $logger);

        $this->settings_repo = $em->getRepository(Entity\Settings::class);

        $this->app_settings = $app_settings;
        $this->azuracast_central = $azuracast_central;
    }

    public function run($force = false): void
    {
        if (!$force) {
            $update_last_run = (int)$this->settings_repo->getSetting(Entity\Settings::UPDATE_LAST_RUN, 0);

            if ($update_last_run > (time()-self::UPDATE_THRESHOLD)) {
                $this->logger->debug('Not checking for updates; checked too recently.');
                return;
            }
        }

        $check_for_updates = (int)$this->settings_repo->getSetting(Entity\Settings::CENTRAL_UPDATES, Entity\Settings::UPDATES_RELEASE_ONLY);

        if (Entity\Settings::UPDATES_NONE === $check_for_updates || $this->app_settings->isTesting()) {
            $this->logger->info('Update checks are currently disabled for this AzuraCast instance.');
            return;
        }

        try {
            $updates = $this->azuracast_central->checkForUpdates();

            if (!empty($updates)) {
                $this->settings_repo->setSetting(Entity\Settings::UPDATE_RESULTS, $updates);
                $this->logger->info('Successfully checked for updates.', ['results' => $updates]);
            } else {
                $this->logger->error('Error parsing update data response from AzuraCast central.');
            }
        } catch(TransferException $e) {
            $this->logger->error(sprintf('Error from AzuraCast Central (%d): %s', $e->getCode(), $e->getMessage()));
            return;
        }

        $this->settings_repo->setSetting(Entity\Settings::UPDATE_LAST_RUN, time());
    }
}
