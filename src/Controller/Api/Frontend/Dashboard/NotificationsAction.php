<?php

namespace App\Controller\Api\Frontend\Dashboard;

use App\Event;
use App\EventDispatcher;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class NotificationsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EventDispatcher $eventDispatcher
    ): ResponseInterface {
        $event = new Event\GetNotifications($request);
        $eventDispatcher->dispatch($event);

        return $response->withJson(
            $event->getNotifications()
        );
    }
}
