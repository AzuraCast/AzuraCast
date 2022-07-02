<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Entity;
use App\Enums\GlobalPermissions;
use App\Enums\ReleaseChannel;
use App\Event\GetNotifications;
use App\Session\Flash;
use App\Version;

final class UpdateCheck
{
    public function __construct(
        private readonly Version $version,
        private readonly Entity\Repository\SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $acl = $event->getRequest()->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::All)) {
            return;
        }

        $settings = $this->settingsRepo->readSettings();

        if (!$settings->getCheckForUpdates()) {
            return;
        }

        $updateData = $settings->getUpdateResults();
        if (empty($updateData)) {
            return;
        }

        $actionLabel = __('Update Instructions');
        $actionUrl = Version::UPDATE_URL;

        $releaseChannel = $this->version->getReleaseChannelEnum();

        if (ReleaseChannel::Stable === $releaseChannel && $updateData['needs_release_update']) {
            $notificationParts = [
                '<b>' . sprintf(
                    __('AzuraCast <a href="%s" target="_blank">version %s</a> is now available.'),
                    Version::CHANGELOG_URL,
                    $updateData['latest_release']
                ) . '</b>',
                sprintf(
                    __('You are currently running version %s. Updating is highly recommended.'),
                    $updateData['current_release']
                ),
            ];

            $notification = new Entity\Api\Notification();
            $notification->title = __('New AzuraCast Release Version Available');
            $notification->body = implode(' ', $notificationParts);
            $notification->type = Flash::INFO;
            $notification->actionLabel = $actionLabel;
            $notification->actionUrl = $actionUrl;

            $event->addNotification($notification);
            return;
        }

        if (ReleaseChannel::RollingRelease === $releaseChannel && $updateData['needs_rolling_update']) {
            $notificationParts = [];

            $notificationParts[] = '<b>' . sprintf(
                __('Your installation is currently %d update(s) behind the latest version.'),
                $updateData['rolling_updates_available']
            ) . '</b>';

            $notificationParts[] = sprintf(
                '<a href="%s" target="_blank">' . __('View the changelog for full details.') . '</a>',
                Version::CHANGELOG_URL
            );
            $notificationParts[] = __('You should update to take advantage of bug and security fixes.');

            $notification = new Entity\Api\Notification();
            $notification->title = __('New AzuraCast Updates Available');
            $notification->body = implode(' ', $notificationParts);
            $notification->type = Flash::INFO;
            $notification->actionLabel = $actionLabel;
            $notification->actionUrl = $actionUrl;

            $event->addNotification($notification);
        }
    }
}
