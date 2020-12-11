<?php

namespace App\Notification\Check;

use App\Acl;
use App\Entity;
use App\Event\GetNotifications;
use App\Notification\Notification;
use App\Sync\Runner;

class SyncTaskCheck
{
    protected Runner $syncRunner;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    public function __construct(
        Runner $syncRunner,
        Entity\Repository\SettingsRepository $settingsRepo
    ) {
        $this->syncRunner = $syncRunner;
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

        $setupComplete = $settings->isSetupComplete();
        $syncTasks = $this->syncRunner->getSyncTimes();

        foreach ($syncTasks as $taskKey => $task) {
            $interval = $task['interval'];
            $diff = $task['diff'];

            // Don't show notification if this installation is freshly installed.
            $threshold = time() - ($interval * 5);
            if ($setupComplete >= $threshold) {
                continue;
            }

            if ($diff > ($interval * 5)) {
                $router = $request->getRouter();
                $backupUrl = $router->named('admin:debug:sync', ['type' => $taskKey]);

                $event->addNotification(
                    new Notification(
                        __('Synchronized Task Not Recently Run'),
                        // phpcs:disable Generic.Files.LineLength
                        __(
                            'The "%s" synchronization task has not run recently. This may indicate an error with your installation. <a href="%s" target="_blank">Manually run the task</a> to check for errors.',
                            $task['name'],
                            $backupUrl
                        ),
                        // phpcs:enable
                        Notification::ERROR
                    )
                );
            }
        }
    }
}
