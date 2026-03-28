<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Entity\Api\Notification;
use App\Enums\FlashLevels;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Service\ServiceControl;

final readonly class ServiceCheck
{
    public function __construct(
        private ServiceControl $serviceControl
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
                $router = $request->getRouter();

                // phpcs:disable Generic.Files.LineLength
                $event->addNotification(
                    new Notification(
                        id: sprintf('notification-service-%s', $service->name),
                        title: sprintf(__('Service Not Running: %s'), $service->name),
                        body: __(
                            'One of the essential services on this installation is not currently running. Visit the system administration and check the system logs to find the cause of this issue.'
                        ),
                        type: FlashLevels::Error,
                        actionLabel: __('Administration'),
                        actionUrl: $router->named('admin:index:index')
                    )
                );
                // phpcs:enable
            }
        }
    }
}
