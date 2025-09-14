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
                __('AzuraCast is free and open-source software.'),
                __(
                    'If you are enjoying AzuraCast, please consider donating to support our work. We depend ' .
                    'on donations to build new features, fix bugs, and keep AzuraCast modern, accessible and free.',
                ),
                FlashLevels::Info,
                __('Donate to AzuraCast'),
                'https://donate.azuracast.com/'
            )
        );
    }
}
