<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Debug\DebugQueue;
use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/debug/queues',
        operationId: 'getAdminDebugQueues',
        summary: 'List all message queues.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: DebugQueue::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class ListQueuesAction implements SingleActionInterface
{
    public function __construct(
        private QueueManagerInterface $queueManager,
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();

        $queueTotals = [];
        foreach (QueueNames::cases() as $queue) {
            $queueTotals[] = new DebugQueue(
                $queue->value,
                $this->queueManager->getQueueCount($queue),
                $router->named(
                    'api:admin:debug:clear-queue',
                    ['queue' => $queue->value]
                )
            );
        }

        return $response->withJson($queueTotals);
    }
}
