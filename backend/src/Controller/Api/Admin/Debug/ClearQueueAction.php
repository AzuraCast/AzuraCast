<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/clear-queue/{queue}',
        operationId: 'adminDebugClearQueue',
        summary: 'Clear the specified message queue.',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        parameters: [
            new OA\Parameter(
                name: 'queue',
                description: 'Message queue type.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    enum: QueueNames::class
                )
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final readonly class ClearQueueAction implements SingleActionInterface
{
    public function __construct(
        private QueueManagerInterface $queueManager
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $queue */
        $queue = $params['queue'] ?? null;

        if (!empty($queue)) {
            $this->queueManager->clearQueue(
                QueueNames::from($queue)
            );
        } else {
            $this->queueManager->clearAllQueues();
        }

        return $response->withJson(Status::updated());
    }
}
