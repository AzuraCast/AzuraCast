<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\Simulcasting;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Simulcasting\SimulcastingManager;
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
    protected string $resourceRouteName = 'api:stations:simulcasting:stream';

    public function __construct(
        \Symfony\Component\Serializer\Serializer $serializer,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator,
        private readonly SimulcastingManager $simulcastingManager,
        private readonly Liquidsoap $liquidsoap
    ) {
        parent::__construct($serializer, $validator);
    }

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
        
        // Persist and flush the entity to save it to the database
        $this->em->persist($simulcasting);
        $this->em->flush();
        
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
    public function startAction(ServerRequest $request, Response $response, array $params): \Psr\Http\Message\ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->id !== $station->id) {
            return $response->withStatus(404)
                ->withJson(\App\Entity\Api\Error::notFound());
        }

        // Start the simulcasting stream using SimulcastingManager
        $success = $this->simulcastingManager->startSimulcasting($simulcasting, $this->liquidsoap);
        
        if (!$success) {
            return $response->withStatus(500)
                ->withJson([
                    'code' => 500,
                    'type' => 'Error',
                    'message' => 'Failed to start simulcasting stream',
                    'success' => false
                ]);
        }

        $return = $this->viewRecord($simulcasting, $request);
        return $response->withJson($return);
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
    public function stopAction(ServerRequest $request, Response $response, array $params): \Psr\Http\Message\ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->id !== $station->id) {
            return $response->withStatus(404)
                ->withJson(\App\Entity\Api\Error::notFound());
        }

        // Stop the simulcasting stream using SimulcastingManager
        $success = $this->simulcastingManager->stopSimulcasting($simulcasting, $this->liquidsoap);
        
        if (!$success) {
            return $response->withStatus(500)
                ->withJson([
                    'code' => 500,
                    'type' => 'Error',
                    'message' => 'Failed to stop simulcasting stream',
                    'success' => false
                ]);
        }

        $return = $this->viewRecord($simulcasting, $request);
        return $response->withJson($return);
    }
}
