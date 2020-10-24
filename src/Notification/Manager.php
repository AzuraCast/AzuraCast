<?php

namespace App\Notification;

use App\Acl;
use App\Entity;
use App\Event\GetNotifications;
use App\Settings;
use App\Version;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Manager implements EventSubscriberInterface
{
    protected Acl $acl;

    protected EntityManagerInterface $em;

    protected Logger $logger;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Settings $appSettings;

    protected Version $version;

    public function __construct(
        Acl $acl,
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Logger $logger,
        Settings $appSettings,
        Version $version
    ) {
        $this->acl = $acl;
        $this->em = $em;
        $this->logger = $logger;
        $this->appSettings = $appSettings;
        $this->settingsRepo = $settingsRepo;
        $this->version = $version;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GetNotifications::class => [
                ['checkComposeVersion', 1],
                ['checkUpdates', 0],
                ['checkRecentBackup', -1],
            ],
        ];
    }

    public function checkComposeVersion(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        if (!$this->acl->userAllowed($event->getCurrentUser(), Acl::GLOBAL_ALL)) {
            return;
        }

        if (!$this->appSettings->isDocker()) {
            return;
        }

        $compose_revision = $_ENV['AZURACAST_DC_REVISION'] ?? 1;

        if ($compose_revision < 5) {
            $event->addNotification(new Notification(
                __('Your <code>docker-compose.yml</code> file is out of date!'),
                // phpcs:disable Generic.Files.LineLength
                __(
                    'You should update your <code>docker-compose.yml</code> file to reflect the newest changes. View the <a href="%s" target="_blank">latest version of the file</a> and update your file accordingly.<br>You can also use the <code>./docker.sh</code> utility script to automatically update your file.',
                    'https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml'
                ),
                // phpcs:enable
                Notification::WARNING
            ));
        }
    }

    public function checkUpdates(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        if (!$this->acl->userAllowed($event->getCurrentUser(), Acl::GLOBAL_ALL)) {
            return;
        }

        $checkForUpdates = (bool)$this->settingsRepo->getSetting(Entity\Settings::CENTRAL_UPDATES, 1);
        if (!$checkForUpdates) {
            return;
        }

        $updateData = $this->settingsRepo->getSetting(Entity\Settings::UPDATE_RESULTS);
        if (empty($updateData)) {
            return;
        }

        $instructions_url = 'https://www.azuracast.com/administration/system/updating.html';
        $instructions_string = __(
            'Follow the <a href="%s" target="_blank">update instructions</a> to update your installation.',
            $instructions_url
        );

        $releaseChannel = $this->version->getReleaseChannel();

        if (Version::RELEASE_CHANNEL_STABLE === $releaseChannel && $updateData['needs_release_update']) {
            $notification_parts = [
                '<b>' . __(
                    'AzuraCast <a href="%s" target="_blank">version %s</a> is now available.',
                    'https://github.com/AzuraCast/AzuraCast/releases',
                    $updateData['latest_release']
                ) . '</b>',
                __(
                    'You are currently running version %s. Updating is highly recommended.',
                    $updateData['current_release']
                ),
                $instructions_string,
            ];

            $event->addNotification(new Notification(
                __('New AzuraCast Release Version Available'),
                implode(' ', $notification_parts),
                Notification::INFO
            ));
            return;
        }

        if (Version::RELEASE_CHANNEL_ROLLING === $releaseChannel && $updateData['needs_rolling_update']) {
            $notification_parts = [];
            if ($updateData['rolling_updates_available'] < 15 && !empty($updateData['rolling_updates_list'])) {
                $notification_parts[] = __('The following improvements have been made since your last update:');
                $notification_parts[] = nl2br('<ul><li>' . implode(
                    '</li><li>',
                    $updateData['rolling_updates_list']
                ) . '</li></ul>');
            } else {
                $notification_parts[] = '<b>' . __(
                    'Your installation is currently %d update(s) behind the latest version.',
                    $updateData['rolling_updates_available']
                ) . '</b>';
                $notification_parts[] = __('You should update to take advantage of bug and security fixes.');
            }

            $notification_parts[] = $instructions_string;

            $event->addNotification(new Notification(
                __('New AzuraCast Updates Available'),
                implode(' ', $notification_parts),
                Notification::INFO
            ));
            return;
        }
    }

    public function checkRecentBackup(GetNotifications $event): void
    {
        if (!$this->acl->userAllowed($event->getCurrentUser(), Acl::GLOBAL_BACKUPS)) {
            return;
        }

        if (!$this->appSettings->isProduction()) {
            return;
        }

        $threshold = CarbonImmutable::now()->subWeeks(2)->getTimestamp();
        $backupLastRun = $this->settingsRepo->getSetting(Entity\Settings::BACKUP_LAST_RUN, 0);

        if ($backupLastRun < $threshold) {
            $router = $event->getRequest()->getRouter();

            $backupUrl = $router->named('admin:backups:index');

            $event->addNotification(new Notification(
                __('Installation Not Recently Backed Up'),
                // phpcs:disable Generic.Files.LineLength
                __(
                    'This installation has not been backed up in the last two weeks. Visit the <a href="%s" target="_blank">Backups</a> page to run a new backup.',
                    $backupUrl
                ),
                // phpcs:enable
                Notification::INFO
            ));
        }
    }
}
