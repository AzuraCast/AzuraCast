<?php

namespace App\Notification\Check;

use App\Acl;
use App\Entity;
use App\Environment;
use App\Event\GetNotifications;
use App\Notification\Notification;
use Carbon\CarbonImmutable;

class RecentBackupCheck
{
    protected Environment $environment;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    public function __construct(
        Environment $environment,
        Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $this->environment = $environment;
        $this->settingsRepo = $settingsRepo;
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for backup administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->userAllowed($request->getUser(), Acl::GLOBAL_BACKUPS)) {
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
            $router = $request->getRouter();
            $backupUrl = $router->named('admin:backups:index');

            $event->addNotification(
                new Notification(
                    __('Installation Not Recently Backed Up'),
                    // phpcs:disable Generic.Files.LineLength
                    __(
                        'This installation has not been backed up in the last two weeks. Visit the <a href="%s" target="_blank">Backups</a> page to run a new backup.',
                        $backupUrl
                    ),
                    // phpcs:enable
                    Notification::INFO
                )
            );
        }
    }
}
