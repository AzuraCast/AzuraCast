<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Automation;

use App\Controller\Api\Admin\StationsController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Sync\Task\RunAutomatedAssignmentTask;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[OA\Put(
    path: '/station/{station_id}/automation/run',
    description: 'Run automated assignment.',
    security: OpenApi::API_KEY_SECURITY,
    tags: ['Stations: Automation'],
    parameters: [
        new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
        ),
        new OA\Response(
            response: 404,
            description: 'Record not found',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
        new OA\Response(
            response: 403,
            description: 'Access denied',
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Error')
        ),
    ]
)]
class RunAction extends StationsController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        RunAutomatedAssignmentTask $syncTask
    ): ResponseInterface {
        $station = $request->getStation();

        try {
            $syncTask->runStation($station, true);
            return $response->withJson(Entity\Api\Status::success());
        } catch (Throwable $e) {
            return $response->withStatus(400)->withJson(Entity\Api\Error::fromException($e));
        }
    }
}
