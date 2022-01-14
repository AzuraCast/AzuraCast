<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Entity\Api\Notification;
use App\Entity\Repository\SettingsRepository;
use App\Enums\GlobalPermissions;
use App\Environment;
use App\Event\GetNotifications;
use App\Session\Flash;
use App\Utilities\Strings;
use App\Version;

class BaseUrlCheck
{
    public function __construct(
        protected SettingsRepository $settingsRepo,
        protected Environment $environment
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();

        $router = $request->getRouter();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::Settings)) {
            return;
        }

        $settings = $this->settingsRepo->readSettings();

        // Base URL mismatch doesn't happen if this setting is enabled.
        if ($settings->getPreferBrowserUrl()) {
            return;
        }

        $baseUriWithRequest = $router->getBaseUrl(true);
        $baseUriWithoutRequest = $router->getBaseUrl(false);

        if ((string)$baseUriWithoutRequest !== (string)$baseUriWithRequest) {
            // phpcs:disable Generic.Files.LineLength
            $notificationBodyParts = [];

            $notificationBodyParts[] = __(
                'You may want to update your base URL to ensure it is correct.'
            );
            $notificationBodyParts[] = __(
                'If you regularly use different URLs to access AzuraCast, you should enable the "Prefer Browser URL" setting.'
            );
            // phpcs:enable Generic.Files.LineLength

            $notification = new Notification();
            $notification->title = __(
                'Your "Base URL" setting (%s) does not match the URL you are currently using (%s).',
                Strings::truncateUrl((string)$baseUriWithoutRequest),
                Strings::truncateUrl((string)$baseUriWithRequest)
            );
            $notification->body = implode(' ', $notificationBodyParts);
            $notification->type = Flash::WARNING;
            $notification->actionLabel = __('System Settings');
            $notification->actionUrl = (string)$router->named('admin:settings:index');

            $event->addNotification($notification);
        }
    }
}
