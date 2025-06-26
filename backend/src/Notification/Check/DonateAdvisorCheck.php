<?php

declare(strict_types=1);

namespace App\Notification\Check;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Api\Notification;
use App\Enums\FlashLevels;
use App\Event\GetNotifications;
use App\Exception\Http\RateLimitExceededException;

final class DonateAdvisorCheck
{
    use EnvironmentAwareTrait;

    public function __invoke(GetNotifications $event): void
    {
        if (!$this->environment->isProduction()) {
            return;
        }

        $request = $event->getRequest();

        $rateLimit = $request->getRateLimit();
        try {
            $rateLimit->checkRequestRateLimit($request, 'notification:donate', 600, 1);
        } catch (RateLimitExceededException) {
            return;
        }

        $event->addNotification(
            new Notification(
                __('SoundMesh is in BETA!'),
                __(
                    'Please make sure to report any issue you may see ' .
                    'directly to us on discord so we can try to resolve it ASAP!',
                ),
                FlashLevels::Info,
                __('Join our Discord'),
                'https://join.immunity.community/'
            )
        );
    }
}
