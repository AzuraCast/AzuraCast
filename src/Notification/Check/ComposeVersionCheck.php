<?php

namespace App\Notification\Check;

use App\Acl;
use App\Entity\Api\Notification;
use App\Environment;
use App\Event\GetNotifications;
use App\Session\Flash;
use App\Version;

class ComposeVersionCheck
{
    protected Environment $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->userAllowed($request->getUser(), Acl::GLOBAL_ALL)) {
            return;
        }

        if (!$this->environment->isDocker()) {
            return;
        }

        if (!$this->environment->isDockerRevisionAtLeast(Version::LATEST_COMPOSE_REVISION)) {
            // phpcs:disable Generic.Files.LineLength
            $notification = new Notification();
            $notification->title = __('Your <code>docker-compose.yml</code> file is out of date!');
            $notification->body = __(
                'You should update your <code>docker-compose.yml</code> file to reflect the newest changes. View the <a href="%s" target="_blank">latest version of the file</a> and update your file accordingly.<br>You can also use the <code>./docker.sh</code> utility script to automatically update your file.',
                'https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml'
            );
            $notification->type = Flash::WARNING;

            $event->addNotification($notification);
        }
    }
}
