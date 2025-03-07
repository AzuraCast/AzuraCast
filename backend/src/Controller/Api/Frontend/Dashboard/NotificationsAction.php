<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\CallableEventDispatcherInterface;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Notification;
use App\Event;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/dashboard/notifications',
    operationId: 'getNotifications',
    description: 'Show all notifications your current account should see.',
    tags: [OpenApi::TAG_MISC],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: Notification::class
                )
            )
        ),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
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
