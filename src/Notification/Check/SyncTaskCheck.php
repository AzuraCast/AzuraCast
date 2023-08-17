<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\SettingsAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Session\FlashLevels;

final class SyncTaskCheck
{
    use SettingsAwareTrait;

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::All)) {
            return;
        }

        $settings = $this->readSettings();

        $setupComplete = $settings->getSetupCompleteTime();
        if ($setupComplete > (time() - 60 * 60 * 2)) {
            return;
        }

        if ($settings->getSyncDisabled()) {
            // phpcs:disable Generic.Files.LineLength
            $notification = new Notification();
            $notification->title = __('Synchronization Disabled');
            $notification->body = __(
                'Routine synchronization is currently disabled. Make sure to re-enable it to resume routine maintenance tasks.'
            );
            $notification->type = FlashLevels::Error->value;
            // phpcs:enable

            $event->addNotification($notification);
            return;
        }

        $syncLastRun = $settings->getSyncLastRun();
        if ($syncLastRun < (time() - 60 * 5)) {
            // phpcs:disable Generic.Files.LineLength
            $notification = new Notification();
            $notification->title = __('Synchronization Not Recently Run');
            $notification->body = __(
                'The routine synchronization task has not run recently. This may indicate an error with your installation.'
            );
            $notification->type = FlashLevels::Error->value;

            $router = $request->getRouter();

            $notification->actionLabel = __('System Debugger');
            $notification->actionUrl = $router->named('admin:debug:index');
            // phpcs:enable

            $event->addNotification($notification);
        }
    }
}
