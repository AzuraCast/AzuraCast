<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Event;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\SlimCallableEventDispatcher\CallableEventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

final class NotificationsAction
{
    public function __construct(
        private readonly CallableEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $event = new Event\GetNotifications($request);
        $this->eventDispatcher->dispatch($event);

        return $response->withJson(
            $event->getNotifications()
        );
    }
}
