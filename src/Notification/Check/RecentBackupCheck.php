<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Acl;
use App\Entity;
use App\Environment;
use App\Event\GetNotifications;
use App\Session\Flash;
use Carbon\CarbonImmutable;

class RecentBackupCheck
{
    public function __construct(
        protected Environment $environment,
        protected Entity\Repository\SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for backup administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(Acl::GLOBAL_BACKUPS)) {
            return;
        }

        if (!$this->environment->isProduction()) {
            return;
        }

        $threshold = CarbonImmutable::now()->subWeeks(2)->getTimestamp();

        // Don't show backup warning for freshly created installations.
        $settings = $this->settingsRepo->readSettings();

        $setupComplete = $settings->getSetupCompleteTime();
        if ($setupComplete >= $threshold) {
            return;
        }

        $backupLastRun = $settings->getBackupLastRun();

        if ($backupLastRun < $threshold) {
            $notification = new Entity\Api\Notification();
            $notification->title = __('Installation Not Recently Backed Up');
            $notification->body = __('This installation has not been backed up in the last two weeks.');
            $notification->type = Flash::INFO;

            $router = $request->getRouter();
            $notification->actionLabel = __('Backups');
            $notification->actionUrl = (string)$router->named('admin:backups:index');

            $event->addNotification($notification);
        }
    }
}
