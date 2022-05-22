<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\TestWebhookMessage;
use App\Utilities\File;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

final class TestAction extends AbstractWebhooksAction
{
    public function __construct(
        EntityManagerInterface $em,
        private readonly MessageBus $messageBus
    ) {
        parent::__construct($em);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id
    ): ResponseInterface {
        $this->requireRecord($request->getStation(), $id);

        $tempFile = File::generateTempPath('webhook_test_' . $id . '.log');
        touch($tempFile);

        $message = new TestWebhookMessage();
        $message->webhookId = $id;
        $message->outputPath = $tempFile;

        $this->messageBus->dispatch($message);

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
