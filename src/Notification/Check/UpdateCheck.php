<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Entity;
use App\Enums\GlobalPermissions;
use App\Enums\ReleaseChannel;
use App\Event\GetNotifications;
use App\Session\FlashLevels;
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

        $router = $event->getRequest()->getRouter();

        $actionLabel = __('Update AzuraCast');
        $actionUrl = $router->named('admin:updates:index');

        $releaseChannel = $this->version->getReleaseChannelEnum();

        if (ReleaseChannel::Stable === $releaseChannel && $updateData['needs_release_update']) {
            $notification = new Entity\Api\Notification();
            $notification->title = __(
                'New AzuraCast Stable Release Available',
            );
            $notification->body = sprintf(
                __(
                    'Version %s is now available. You are currently running version %s. Updating is recommended.'
                ),
                $updateData['latest_release'],
                $updateData['current_release']
            );
            $notification->type = FlashLevels::Info->value;
            $notification->actionLabel = $actionLabel;
            $notification->actionUrl = $actionUrl;

            $event->addNotification($notification);
            return;
        }

        if (ReleaseChannel::RollingRelease === $releaseChannel && $updateData['needs_rolling_update']) {
            $notification = new Entity\Api\Notification();
            $notification->title = __(
                'New AzuraCast Rolling Release Available'
            );
            $notification->body = sprintf(
                __('Your installation is currently %d update(s) behind the latest version. Updating is recommended.'),
                $updateData['rolling_updates_available']
            );
            $notification->type = FlashLevels::Info->value;
            $notification->actionLabel = $actionLabel;
            $notification->actionUrl = $actionUrl;

            $event->addNotification($notification);
        }
    }
}
