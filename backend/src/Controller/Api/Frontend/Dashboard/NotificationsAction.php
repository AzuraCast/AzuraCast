<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\CallableEventDispatcherInterface;
use App\Controller\SingleActionInterface;
use App\Event;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class NotificationsAction implements SingleActionInterface
{
    public function __construct(
        private readonly CallableEventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $event = new Event\GetNotifications($request);
        $this->eventDispatcher->dispatch($event);

        return $response->withJson(
            $event->getNotifications()
        );
    }
}
