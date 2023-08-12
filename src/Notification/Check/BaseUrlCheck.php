<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\SettingsAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Session\FlashLevels;

final class BaseUrlCheck
{
    use SettingsAwareTrait;

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $request = $event->getRequest();

        $router = $request->getRouter();
        $acl = $request->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::Settings)) {
            return;
        }

        $settings = $this->readSettings();

        // Base URL mismatch doesn't happen if this setting is enabled.
        if ($settings->getPreferBrowserUrl()) {
            return;
        }

        $baseUriWithRequest = $router->buildBaseUrl(true);
        $baseUriWithoutRequest = $router->buildBaseUrl(false);

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
            $notification->title = sprintf(
                __('Your "Base URL" setting (%s) does not match the URL you are currently using (%s).'),
                (string)$baseUriWithoutRequest,
                (string)$baseUriWithRequest
            );
            $notification->body = implode(' ', $notificationBodyParts);
            $notification->type = FlashLevels::Warning->value;
            $notification->actionLabel = __('System Settings');
            $notification->actionUrl = $router->named('admin:settings:index');

            $event->addNotification($notification);
        }
    }
}
