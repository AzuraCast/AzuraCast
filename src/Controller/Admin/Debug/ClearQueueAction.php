<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Http\Response;
use App\Http\ServerRequest;
use App\MessageQueue\QueueManagerInterface;
use App\MessageQueue\QueueNames;
use App\Session\Flash;
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
        $request->getFlash()->addMessage(
            __('Message queue cleared.'),
            Flash::SUCCESS
        );

        return $response->withRedirect($request->getRouter()->fromHere('admin:debug:index'));
    }
}
