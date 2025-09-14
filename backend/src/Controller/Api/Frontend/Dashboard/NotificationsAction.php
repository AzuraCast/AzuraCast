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
    path: '/frontend/dashboard/notifications',
    operationId: 'getNotifications',
    summary: 'Show all notifications your current account should see.',
    tags: [OpenApi::TAG_MISC],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: Notification::class
                )
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class NotificationsAction implements SingleActionInterface
{
    public function __construct(
        private CallableEventDispatcherInterface $eventDispatcher,
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
