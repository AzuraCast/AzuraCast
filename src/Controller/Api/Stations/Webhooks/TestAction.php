<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\TestWebhookMessage;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

final class TestAction
{
    public function __construct(
        private readonly StationWebhookRepository $webhookRepo,
        private readonly MessageBus $messageBus
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $webhook = $this->webhookRepo->requireForStation($id, $request->getStation());

        $tempFile = File::generateTempPath('webhook_test_' . $id . '.log');
        touch($tempFile);

        $message = new TestWebhookMessage();
        $message->webhookId = $webhook->getIdRequired();
        $message->outputPath = $tempFile;

        $this->messageBus->dispatch($message);

        $router = $request->getRouter();
        return $response->withJson(
            [
                'success' => true,
                'links' => [
                    'log' => $router->fromHere('api:stations:webhook:test-log', [
                        'path' => basename($tempFile),
                    ]),
                ],
            ]
        );
    }
}
