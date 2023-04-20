<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use Psr\Http\Message\ResponseInterface;

final class ClearQueueAction
{
    public function __construct(
        private readonly QueueManagerInterface $queueManager
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        ?string $queue = null
    ): ResponseInterface {
        if (!empty($queue)) {
            $this->queueManager->clearQueue(
                QueueNames::from($queue)
            );
        } else {
            $this->queueManager->clearAllQueues();
        }

        // Flash an update to ensure the session is recreated.
        $request->getFlash()->success(__('Message queue cleared.'));

        return $response->withRedirect($request->getRouter()->fromHere('admin:debug:index'));
    }
}
