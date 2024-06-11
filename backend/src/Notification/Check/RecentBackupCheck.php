<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Session\FlashLevels;
use Carbon\CarbonImmutable;

final class RecentBackupCheck
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for backup administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::Backups)) {
            return;
        }

        if (!$this->environment->isProduction()) {
            return;
        }

        $threshold = CarbonImmutable::now()->subWeeks(2)->getTimestamp();

        // Don't show backup warning for freshly created installations.
        $settings = $this->readSettings();

        $setupComplete = $settings->getSetupCompleteTime();
        if ($setupComplete >= $threshold) {
            return;
        }

        $backupLastRun = $settings->getBackupLastRun();

        if ($backupLastRun < $threshold) {
            $notification = new Notification();
            $notification->title = __('Installation Not Recently Backed Up');
            $notification->body = __('This installation has not been backed up in the last two weeks.');
            $notification->type = FlashLevels::Info->value;

            $router = $request->getRouter();
            $notification->actionLabel = __('Backups');
            $notification->actionUrl = $router->named('admin:backups:index');

            $event->addNotification($notification);
        }
    }
}
