<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\SettingsAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\FlashLevels;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;

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

        $setupComplete = $settings->setup_complete_time;
        if ($setupComplete > (time() - 60 * 60 * 2)) {
            return;
        }

        if ($settings->sync_disabled) {
            // phpcs:disable Generic.Files.LineLength
            $event->addNotification(
                new Notification(
                    __('Synchronization Disabled'),
                    __(
                        'Routine synchronization is currently disabled. Make sure to re-enable it to resume routine maintenance tasks.'
                    ),
                    FlashLevels::Error
                )
            );
            // phpcs:enable

            return;
        }

        $syncLastRun = $settings->sync_last_run;
        if ($syncLastRun < (time() - 60 * 5)) {
            $router = $request->getRouter();

            // phpcs:disable Generic.Files.LineLength
            $event->addNotification(
                new Notification(
                    __('Synchronization Not Recently Run'),
                    __(
                        'The routine synchronization task has not run recently. This may indicate an error with your installation.'
                    ),
                    FlashLevels::Error,
                    __('System Debugger'),
                    $router->named('admin:debug:index')
                )
            );
            // phpcs:enable
        }
    }
}
