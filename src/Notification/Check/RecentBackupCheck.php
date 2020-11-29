<?php

namespace App\Notification\Check;

use App\Acl;
use App\Entity;
use App\Event\GetNotifications;
use App\Notification\Notification;
use App\Settings;
use Carbon\CarbonImmutable;

class RecentBackupCheck
{
    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Settings $appSettings;

    public function __construct(Entity\Repository\SettingsRepository $settingsRepo, Settings $appSettings)
    {
        $this->settingsRepo = $settingsRepo;
        $this->appSettings = $appSettings;
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for backup administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->userAllowed($request->getUser(), Acl::GLOBAL_BACKUPS)) {
            return;
        }

        if (!$this->appSettings->isProduction()) {
            return;
        }

        $threshold = CarbonImmutable::now()->subWeeks(2)->getTimestamp();
        $backupLastRun = $this->settingsRepo->getSetting(Entity\Settings::BACKUP_LAST_RUN, 0);

        if ($backupLastRun < $threshold) {
            $router = $request->getRouter();
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
