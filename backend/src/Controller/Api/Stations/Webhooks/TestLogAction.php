<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Webhooks;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\LogContents;
use App\Entity\Repository\StationWebhookRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/webhooks/{id}/test-log/{path}',
        operationId: 'getWebhookTestLog',
        summary: 'View a specific webhook test dispatch log contents.',
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
            new OA\Parameter(
                name: 'path',
                description: 'Log path as returned by the Test action.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: LogContents::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final class TestLogAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __construct(
        private readonly StationWebhookRepository $webhookRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        /** @var string $path */
        $path = $params['path'];

        $this->webhookRepo->requireForStation($id, $request->getStation());

        $logPathPortion = 'webhook_test_' . $id;
        if (!str_contains($path, $logPathPortion)) {
            return $response
                ->withStatus(403)
                ->withJson(new Error(403, 'Invalid log path.'));
        }

        $tempPath = File::validateTempPath($path);

        return $this->streamLogToResponse(
            $request,
            $response,
            $tempPath
        );
    }
}
