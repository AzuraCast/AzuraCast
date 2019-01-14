<?php
namespace App\Notification;

use App\Acl;
use App\Entity;
use App\Event\GetNotifications;
use Azura\Settings;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Manager implements EventSubscriberInterface
{
    /** @var Acl */
    protected $acl;

    /** @var EntityManager */
    protected $em;

    /** @var Logger */
    protected $logger;

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var Settings */
    protected $app_settings;

    /**
     * Manager constructor.
     *
     * @param Acl $acl
     * @param EntityManager $em
     * @param Logger $logger
     * @param Settings $app_settings
     *
     * @see \App\Provider\NotificationProvider
     */
    public function __construct(Acl $acl, EntityManager $em, Logger $logger, Settings $app_settings)
    {
        $this->acl = $acl;
        $this->em = $em;
        $this->logger = $logger;
        $this->app_settings = $app_settings;

        $this->settings_repo = $this->em->getRepository(Entity\Settings::class);
    }

    public static function getSubscribedEvents()
    {
        return [
            GetNotifications::NAME => [
                ['checkComposeVersion', 1],
                ['checkUpdates', 0],
            ],
        ];
    }

    public function checkComposeVersion(GetNotifications $event)
    {
        // This notification is for full administrators only.
        if (!$this->acl->userAllowed($event->getCurrentUser(), 'administer all')) {
            return;
        }
        
        if (!$this->app_settings->isDocker()) {
            return;
        }

        $compose_revision = $_ENV['AZURACAST_DC_REVISION'] ?? 1;

        if ($compose_revision < 5) {
            $event->addNotification(new Notification(
                __('Your <code>docker-compose.yml</code> file is out of date!'),
                __('You should update your <code>docker-compose.yml</code> file to reflect the newest changes. View the <a href="%s" target="_blank">latest version of the file</a> and update your file accordingly.<br>You can also use the <code>./docker.sh</code> utility script to automatically update your file.', 'https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml'),
                Notification::WARNING
            ));
        }
    }

    public function checkUpdates(GetNotifications $event)
    {
        // This notification is for full administrators only.
        if (!$this->acl->userAllowed($event->getCurrentUser(), 'administer all')) {
            return;
        }

        $check_for_updates = (int)$this->settings_repo->getSetting(Entity\Settings::CENTRAL_UPDATES, 1);

        if (Entity\Settings::UPDATES_NONE === $check_for_updates) {
            return;
        }

        $update_data = $this->settings_repo->getSetting(Entity\Settings::UPDATE_RESULTS);

        if (empty($update_data)) {
            return;
        }

        $instructions_url = 'https://www.azuracast.com/install.html';

        if ($update_data['needs_release_update']) {
            $event->addNotification(new Notification(
                __('New AzuraCast Release Version Available'),
                __('<b>AzuraCast version %s is now available.</b> You are currently running version %s. Updating is highly recommended. Follow the <a href="%s" target="_blank">update instructions</a> to update your installation.', $update_data['latest_release'], $update_data['current_release'], $instructions_url),
                Notification::INFO
            ));
            return;
        }

        if (Entity\Settings::UPDATES_ALL === $check_for_updates && $update_data['needs_rolling_update']) {
            $event->addNotification(new Notification(
                __('New AzuraCast Updates Available'),
                __('<b>Your installation is currently %d update(s) behind the latest version.</b> You should update to take advantage of bug and security fixes. Follow the <a href="%s" target="_blank">update instructions</a> to update your installation.', $update_data['rolling_updates_available'], $instructions_url),
                Notification::INFO
            ));
            return;
        }
    }
}
