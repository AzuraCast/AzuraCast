<?php

namespace App\Notification\Check;

use App\Acl;
use App\Entity;
use App\Event\GetNotifications;
use App\Session\Flash;
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
        if (!$acl->isAllowed(Acl::GLOBAL_ALL)) {
            return;
        }

        $settings = $this->settingsRepo->readSettings();

        $setupComplete = $settings->getSetupCompleteTime();
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
                // phpcs:disable Generic.Files.LineLength
                $notification = new Entity\Api\Notification();
                $notification->title = __('Synchronized Task Not Recently Run');
                $notification->body = __(
                    'The "%s" synchronization task has not run recently. This may indicate an error with your installation.',
                    $task['name']
                );
                $notification->type = Flash::ERROR;

                $router = $request->getRouter();

                $notification->actionLabel = __('Manually Run Task');
                $notification->actionUrl = (string)$router->named('admin:debug:sync', ['type' => $taskKey]);
                // phpcs:enable

                $event->addNotification($notification);
            }
        }
    }
}
