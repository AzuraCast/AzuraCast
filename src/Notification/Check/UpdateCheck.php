<?php

namespace App\Notification\Check;

use App\Acl;
use App\Entity;
use App\Event\GetNotifications;
use App\Session\Flash;
use App\Version;

class UpdateCheck
{
    protected Version $version;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    public function __construct(Version $version, Entity\Repository\SettingsRepository $settingsRepo)
    {
        $this->version = $version;
        $this->settingsRepo = $settingsRepo;
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->userAllowed($request->getUser(), Acl::GLOBAL_ALL)) {
            return;
        }

        $settings = $this->settingsRepo->readSettings();

        $checkForUpdates = $settings->getCheckForUpdates();
        if (!$checkForUpdates) {
            return;
        }

        $updateData = $settings->getUpdateResults();
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

            $notification = new Entity\Api\Notification();
            $notification->title = __('New AzuraCast Release Version Available');
            $notification->body = implode(' ', $notification_parts);
            $notification->type = Flash::INFO;

            $event->addNotification($notification);
            return;
        }

        if (Version::RELEASE_CHANNEL_ROLLING === $releaseChannel) { // } && $updateData['needs_rolling_update']) {
            $notification_parts = [];
            if ($updateData['rolling_updates_available'] < 15 && !empty($updateData['rolling_updates_list'])) {
                $notification_parts[] = __('The following improvements have been made since your last update:');
                $notification_parts[] = nl2br(
                    '<ul><li>' . implode(
                        '</li><li>',
                        $updateData['rolling_updates_list']
                    ) . '</li></ul>'
                );
            } else {
                $notification_parts[] = '<b>' . __(
                    'Your installation is currently %d update(s) behind the latest version.',
                    $updateData['rolling_updates_available']
                ) . '</b>';
                $notification_parts[] = __('You should update to take advantage of bug and security fixes.');
            }

            $notification_parts[] = $instructions_string;

            $notification = new Entity\Api\Notification();
            $notification->title = __('New AzuraCast Updates Available');
            $notification->body = implode(' ', $notification_parts);
            $notification->type = Flash::INFO;

            $event->addNotification($notification);
            return;
        }
    }
}
