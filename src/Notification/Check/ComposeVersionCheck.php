<?php

namespace App\Notification\Check;

use App\Acl;
use App\Event\GetNotifications;
use App\Notification\Notification;
use App\Settings;

class ComposeVersionCheck
{
    protected Settings $appSettings;

    public function __construct(Settings $appSettings)
    {
        $this->appSettings = $appSettings;
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();
        $acl = $request->getAcl();
        if (!$acl->userAllowed($request->getUser(), Acl::GLOBAL_ALL)) {
            return;
        }

        if (!$this->appSettings->isDocker()) {
            return;
        }

        $compose_revision = $_ENV['AZURACAST_DC_REVISION'] ?? 1;

        if ($compose_revision < 5) {
            $event->addNotification(new Notification(
                __('Your <code>docker-compose.yml</code> file is out of date!'),
                // phpcs:disable Generic.Files.LineLength
                __(
                    'You should update your <code>docker-compose.yml</code> file to reflect the newest changes. View the <a href="%s" target="_blank">latest version of the file</a> and update your file accordingly.<br>You can also use the <code>./docker.sh</code> utility script to automatically update your file.',
                    'https://raw.githubusercontent.com/AzuraCast/AzuraCast/master/docker-compose.sample.yml'
                ),
                // phpcs:enable
                Notification::WARNING
            ));
        }
    }
}
