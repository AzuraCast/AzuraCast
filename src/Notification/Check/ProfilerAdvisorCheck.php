<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Acl;
use App\Entity\Api\Notification;
use App\Environment;
use App\Event\GetNotifications;
use App\Session\Flash;

class ProfilerAdvisorCheck
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

        $profilerIsEnabled = $this->environment->isProfilingExtensionEnabled();
        if (!$profilerIsEnabled) {
            return;
        }

        $notification = new Notification();
        $notification->title = __('The performance profiling extension is currently enabled on this installation.');
        $notification->body = __(
            'You can track the execution time and memory usage of any AzuraCast page or application ' .
            'from the profiler page.',
        );
        $notification->type = Flash::INFO;

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
            $notification->type = Flash::WARNING;

            $event->addNotification($notification);
        }
    }
}
