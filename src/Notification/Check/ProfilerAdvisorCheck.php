<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\GlobalPermissions;
use App\Event\GetNotifications;
use App\Session\FlashLevels;

final class ProfilerAdvisorCheck
{
    use EnvironmentAwareTrait;

    public function __invoke(GetNotifications $event): void
    {
        // This notification is for full administrators only.
        $acl = $event->getRequest()->getAcl();
        if (!$acl->isAllowed(GlobalPermissions::All)) {
            return;
        }

        if (!$this->environment->isDocker()) {
            return;
        }

        if (!$this->environment->isProfilingExtensionEnabled()) {
            return;
        }

        $notification = new Notification();
        $notification->title = __('The performance profiling extension is currently enabled on this installation.');
        $notification->body = __(
            'You can track the execution time and memory usage of any AzuraCast page or application ' .
            'from the profiler page.',
        );
        $notification->type = FlashLevels::Info->value;

        $notification->actionLabel = __('Profiler Control Panel');
        $notification->actionUrl = '/?' . http_build_query(
            [
                    'SPX_UI_URI' => '/',
                    'SPX_KEY' => $this->environment->getProfilingExtensionHttpKey(),
                ]
        );

        $event->addNotification($notification);

        if ($this->environment->isProfilingExtensionAlwaysOn()) {
            $notification = new Notification();
            $notification->title = __('Performance profiling is currently enabled for all requests.');
            $notification->body = __(
                'This can have an adverse impact on system performance. ' .
                'You should disable this when possible.'
            );
            $notification->type = FlashLevels::Warning->value;

            $event->addNotification($notification);
        }
    }
}
