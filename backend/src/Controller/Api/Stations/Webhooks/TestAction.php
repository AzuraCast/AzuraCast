<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\TaskWithLog;
use App\Entity\Repository\StationWebhookRepository;
use App\Enums\GlobalPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\TestWebhookMessage;
use App\OpenApi;
use App\Utilities\File;
use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

#[OA\Put(
    path: '/station/{station_id}/webhook/{id}/test',
    operationId: 'putStationWebhookTest',
    summary: 'Send a test dispatch of a webhook with the current Now Playing data for the station.',
    tags: [OpenApi::TAG_STATIONS_WEBHOOKS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Web Hook ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: TaskWithLog::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
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
        $message->webhookId = $webhook->id;
        $message->outputPath = $tempFile;
        $message->logLevel = $logLevel->value;

        $this->messageBus->dispatch($message);

        $router = $request->getRouter();
        return $response->withJson(
            new TaskWithLog(
                $router->fromHere('api:stations:webhook:test-log', [
                    'path' => basename($tempFile),
                ])
            )
        );
    }
}
