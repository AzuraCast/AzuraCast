<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\Simulcasting;
use App\Exception\ValidationException;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;

/** @extends AbstractStationApiCrudController<Simulcasting> */
#[
    OA\Get(
        path: '/station/{station_id}/simulcasting',
        operationId: 'getSimulcasting',
        summary: 'List all current simulcasting streams.',
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: Simulcasting::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/simulcasting',
        operationId: 'addSimulcasting',
        summary: 'Create a new simulcasting stream.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: Simulcasting::class)
        ),
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: Simulcasting::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/simulcasting/{id}',
        operationId: 'getSimulcasting',
        summary: 'Retrieve details for a single simulcasting stream.',
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting Stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: Simulcasting::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/simulcasting/{id}',
        operationId: 'editSimulcasting',
        summary: 'Update details of a single simulcasting stream.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: Simulcasting::class)
        ),
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting Stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/simulcasting/{id}',
        operationId: 'deleteSimulcasting',
        summary: 'Delete a single simulcasting stream.',
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting Stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class SimulcastingController extends AbstractStationApiCrudController
{
    protected string $entityClass = Simulcasting::class;
    protected string $resourceRouteName = 'api:stations:simulcasting';

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();
        
        // Create the simulcasting stream
        $simulcasting = new Simulcasting(
            $station,
            $data['name'],
            $data['adapter'],
            $data['stream_key']
        );
        
        return $simulcasting;
    }

    #[OA\Post(
        path: '/station/{station_id}/simulcasting/{id}/start',
        operationId: 'startSimulcasting',
        summary: 'Start a simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting Stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function startAction(ServerRequest $request, array $params): \Psr\Http\Message\ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $this->response, 'Simulcasting stream not found', [], 404);
        }

        // Start the simulcasting stream
        $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Starting);
        $this->em->persist($simulcasting);
        $this->em->flush();

        return $this->renderSuccess($request, $this->response, $simulcasting);
    }

    #[OA\Post(
        path: '/station/{station_id}/simulcasting/{id}/stop',
        operationId: 'stopSimulcasting',
        summary: 'Stop a simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_SIMULCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting Stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function stopAction(ServerRequest $request, array $params): \Psr\Http\Message\ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $this->response, 'Simulcasting stream not found', [], 404);
        }

        // Stop the simulcasting stream
        $simulcasting->setStatus(\App\Entity\Enums\SimulcastingStatus::Stopped);
        $this->em->persist($simulcasting);
        $this->em->flush();

        return $this->renderSuccess($request, $this->response, $simulcasting);
    }
}
