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
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
