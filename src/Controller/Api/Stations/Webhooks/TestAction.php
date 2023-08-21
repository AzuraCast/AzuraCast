<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationWebhookRepository;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\TestWebhookMessage;
use App\Utilities\File;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

final class TestAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly StationWebhookRepository $webhookRepo,
        private readonly MessageBus $messageBus
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();
        $acl = $request->getAcl();

        $webhook = $this->webhookRepo->requireForStation($id, $station);

        $logLevel = ($this->environment->isDevelopment() || $acl->isAllowed(GlobalPermissions::View))
            ? Level::Debug
            : Level::Info;

        $tempFile = File::generateTempPath('webhook_test_' . $id . '.log');
        touch($tempFile);

        $message = new TestWebhookMessage();
        $message->webhookId = $webhook->getIdRequired();
        $message->outputPath = $tempFile;
        $message->logLevel = $logLevel->value;

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
