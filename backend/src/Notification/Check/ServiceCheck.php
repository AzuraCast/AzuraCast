<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Entity\Api\Notification;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Service\ServiceControl;
use App\Session\FlashLevels;

final class ServiceCheck
{
    public function __construct(
        private readonly ServiceControl $serviceControl
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::View)) {
            return;
        }

        $services = $this->serviceControl->getServices();
        foreach ($services as $service) {
            if (!$service->running) {
                // phpcs:disable Generic.Files.LineLength
                $notification = new Notification();
                $notification->title = sprintf(__('Service Not Running: %s'), $service->name);
                $notification->body = __(
                    'One of the essential services on this installation is not currently running. Visit the system administration and check the system logs to find the cause of this issue.'
                );
                $notification->type = FlashLevels::Error->value;

                $router = $request->getRouter();

                $notification->actionLabel = __('Administration');
                $notification->actionUrl = $router->named('admin:index:index');
                // phpcs:enable

                $event->addNotification($notification);
            }
        }
    }
}
