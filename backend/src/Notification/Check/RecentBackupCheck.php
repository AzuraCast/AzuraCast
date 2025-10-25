<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\FlashLevels;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
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

        $setupComplete = $settings->setup_complete_time;
        if ($setupComplete >= $threshold) {
            return;
        }

        $backupLastRun = $settings->backup_last_run;

        if ($backupLastRun < $threshold) {
            $router = $request->getRouter();
            $event->addNotification(
                new Notification(
                    id: 'notification-recent-backup',
                    title: __('Installation Not Recently Backed Up'),
                    body: __('This installation has not been backed up in the last two weeks.'),
                    type: FlashLevels::Info,
                    actionLabel: __('Backups'),
                    actionUrl: $router->named('admin:backups:index')
                )
            );
        }
    }
}
