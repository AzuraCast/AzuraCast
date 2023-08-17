<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Entity\Api\Notification;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Service\HighAvailability;
use App\Session\FlashLevels;

final class ActiveServerCheck
{
    public function __construct(
        private readonly HighAvailability $highAvailability
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::All)) {
            return;
        }

        if (!$this->highAvailability->isActiveServer()) {
            // phpcs:disable Generic.Files.LineLength
            $notification = new Notification();
            $notification->title = __('This server is not the current active instance.');
            $notification->body = __(
                'This likely means that multiple AzuraCast instances are connecting to the same database. This instance is not the current active one, so synchronized tasks won\'t run on this server. If this is intentional, you can disregard this message.'
            );
            $notification->type = FlashLevels::Warning->value;
            // phpcs:enable

            $event->addNotification($notification);
        }
    }
}
