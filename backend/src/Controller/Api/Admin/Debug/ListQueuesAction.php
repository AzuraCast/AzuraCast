<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use Psr\Http\Message\ResponseInterface;

final class ListQueuesAction implements SingleActionInterface
{
    public function __construct(
        private readonly QueueManagerInterface $queueManager,
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();

        $queueTotals = [];
        foreach (QueueNames::cases() as $queue) {
            $queueTotals[] = [
                'name' => $queue->value,
                'count' => $this->queueManager->getQueueCount($queue),
                'url' => $router->named(
                    'api:admin:debug:clear-queue',
                    ['queue' => $queue->value]
                ),
            ];
        }

        return $response->withJson($queueTotals);
    }
}
