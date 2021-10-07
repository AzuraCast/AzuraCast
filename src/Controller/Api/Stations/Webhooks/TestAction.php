<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\TestWebhookMessage;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

class TestAction extends AbstractWebhooksAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        MessageBus $messageBus,
        int $id
    ): ResponseInterface {
        $this->requireRecord($request->getStation(), $id);

        $tempFile = File::generateTempPath('webhook_test_' . $id . '.log');

        $message = new TestWebhookMessage();
        $message->webhookId = $id;
        $message->outputPath = $tempFile;

        $messageBus->dispatch($message);

        $router = $request->getRouter();
        return $response->withJson(
            [
                'success' => true,
                'links' => [
                    'log' => (string)$router->fromHere('api:stations:webhook:test-log', [
                        'path' => basename($tempFile),
                    ]),
                ],
            ]
        );
    }
}
