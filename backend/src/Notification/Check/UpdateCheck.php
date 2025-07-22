<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\SettingsAwareTrait;
use App\Entity\Api\Admin\UpdateDetails;
use App\Entity\Api\Notification;
use App\Enums\FlashLevels;
use App\Enums\GlobalPermissions;
use App\Enums\ReleaseChannel;
use App\Event\GetNotifications;
use App\Version;

final class UpdateCheck
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly Version $version,
    ) {
    }

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $acl = $event->getRequest()->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::All)) {
            return;
        }

        $settings = $this->readSettings();
        if (!$settings->check_for_updates) {
            return;
        }

        $updateDataRaw = $settings->update_results;
        if (empty($updateDataRaw)) {
            return;
        }

        $updateData = UpdateDetails::fromParent($updateDataRaw);

        $router = $event->getRequest()->getRouter();

        $actionLabel = __('Update AzuraCast');
        $actionUrl = $router->named('admin:updates:index');

        $releaseChannel = $this->version->getReleaseChannelEnum();

        if (
            ReleaseChannel::Stable === $releaseChannel
            && ($updateData->needs_release_update)
        ) {
            $event->addNotification(
                new Notification(
                    __(
                        'New AzuraCast Stable Release Available',
                    ),
                    sprintf(
                        __(
                            'Version %s is now available. You are currently running version %s. Updating is recommended.'
                        ),
                        $updateData->latest_release,
                        $updateData->current_release
                    ),
                    FlashLevels::Info,
                    $actionLabel,
                    $actionUrl
                )
            );
            return;
        }

        if (ReleaseChannel::RollingRelease === $releaseChannel) {
            if ($updateData->needs_rolling_update) {
                $event->addNotification(
                    new Notification(
                        __('New AzuraCast Rolling Release Available'),
                        sprintf(
                            __(
                                'Your installation is currently %d update(s) behind the latest version. Updating is recommended.'
                            ),
                            $updateData->rolling_updates_available
                        ),
                        FlashLevels::Info,
                        $actionLabel,
                        $actionUrl
                    )
                );
            }

            if ($updateData->can_switch_to_stable) {
                $event->addNotification(
                    new Notification(
                        __('Switch to Stable Channel Available'),
                        __(
                            'Your Rolling Release installation is currently older than the latest Stable release. This means you can switch releases to the "Stable" release channel if desired.'
                        ),
                        FlashLevels::Info,
                        __('About Release Channels'),
                        '/docs/getting-started/updates/release-channels/'
                    )
                );
            }
        }
    }
}
