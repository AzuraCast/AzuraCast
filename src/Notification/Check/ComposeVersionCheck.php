<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Acl;
use App\Entity\Api\Notification;
use App\Environment;
use App\Event\GetNotifications;
use App\Session\Flash;
use App\Version;

class ComposeVersionCheck
{
    public function __construct(
        protected Environment $environment
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $acl = $event->getRequest()->getAcl();
        if (!$acl->isAllowed(Acl::GLOBAL_ALL)) {
            return;
        }

        if (!$this->environment->isDocker()) {
            return;
        }

        if (!$this->environment->isDockerRevisionAtLeast(Version::LATEST_COMPOSE_REVISION)) {
            // phpcs:disable Generic.Files.LineLength
            $notificationBodyParts = [];

            $notificationBodyParts[] = __(
                'You should update your <code>docker-compose.yml</code> file to reflect the newest changes.'
            );
            $notificationBodyParts[] = __(
                'If you manually maintain this file, review the <a href="%s" target="_blank">latest version of the file</a> and make any changes needed.',
                Version::LATEST_COMPOSE_URL
            );
            $notificationBodyParts[] = __(
                'Otherwise, update your installation and answer "Y" when prompted to update the file.',
            );
            // phpcs:enable Generic.Files.LineLength

            $notification = new Notification();
            $notification->title = __('Your <code>docker-compose.yml</code> file is out of date!');
            $notification->body = implode(' ', $notificationBodyParts);
            $notification->type = Flash::WARNING;
            $notification->actionLabel = __('Update Instructions');
            $notification->actionUrl = Version::UPDATE_URL;

            $event->addNotification($notification);
        }
    }
}
