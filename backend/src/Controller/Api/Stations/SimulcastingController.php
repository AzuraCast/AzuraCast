<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiController;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\Simulcasting;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Simulcasting\SimulcastingManager;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(
    name: OpenApi::TAG_STATIONS_BROADCASTING,
    description: 'Station simulcasting management'
)]
final class SimulcastingController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SimulcastingManager $simulcastingManager,
        private readonly Adapters $adapters,
        private readonly ValidatorInterface $validator,
        LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    #[OA\Get(
        path: '/station/{station_id}/simulcasting',
        operationId: 'getStationSimulcasting',
        summary: 'List all simulcasting streams for a station',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: OA\Type::ARRAY,
                    items: new OA\Items(ref: '#/components/schemas/Simulcasting')
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        
        $simulcastingStreams = $this->em->getRepository(Simulcasting::class)
            ->findByStation($station);

        return $response->withJson($simulcastingStreams);
    }

    #[OA\Post(
        path: '/station/{station_id}/simulcasting',
        operationId: 'createStationSimulcasting',
        summary: 'Create a new simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['name', 'adapter', 'stream_key'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'adapter', type: 'string', maxLength: 50),
                    new OA\Property(property: 'stream_key', type: 'string', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\ValidationError(),
        ]
    )]
    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $data = $request->getParsedBody();

        // Validate input
        $constraints = $this->getValidationConstraints();
        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            return $this->renderValidationError($request, $response, $violations);
        }

        // Validate adapter
        if (!$this->simulcastingManager->validateAdapter($data['adapter'])) {
            return $this->renderError($request, $response, 'Invalid adapter specified', [], 400);
        }

        // Create simulcasting stream
        $simulcasting = new Simulcasting(
            $station,
            $data['name'],
            $data['adapter'],
            $data['stream_key']
        );

        $this->em->persist($simulcasting);
        $this->em->flush();

        $this->logger->info('Simulcasting stream created', [
            'station_id' => $station->id,
            'simulcasting_id' => $simulcasting->getId(),
            'adapter' => $data['adapter'],
        ]);

        return $this->renderSuccess($request, $response, $simulcasting, [], 201);
    }

    #[OA\Get(
        path: '/station/{station_id}/simulcasting/{id}',
        operationId: 'getStationSimulcastingStream',
        summary: 'Get a specific simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: '#/components/schemas/Simulcasting')
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function getAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $response, 'Simulcasting stream not found', [], 404);
        }

        return $this->renderSuccess($request, $response, $simulcasting);
    }

    #[OA\Put(
        path: '/station/{station_id}/simulcasting/{id}',
        operationId: 'updateStationSimulcasting',
        summary: 'Update a simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'adapter', type: 'string', maxLength: 50),
                    new OA\Property(property: 'stream_key', type: 'string', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\ValidationError(),
        ]
    )]
    public function updateAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];
        $data = $request->getParsedBody();

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $response, 'Simulcasting stream not found', [], 404);
        }

        // Validate input
        $constraints = $this->getValidationConstraints();
        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            return $this->renderValidationError($request, $response, $violations);
        }

        // Validate adapter if changed
        if (isset($data['adapter']) && $data['adapter'] !== $simulcasting->getAdapter()) {
            if (!$this->simulcastingManager->validateAdapter($data['adapter'])) {
                return $this->renderError($request, $response, 'Invalid adapter specified', [], 400);
            }
        }

        // Update fields
        if (isset($data['name'])) {
            $simulcasting->setName($data['name']);
        }
        if (isset($data['adapter'])) {
            $simulcasting->setAdapter($data['adapter']);
        }
        if (isset($data['stream_key'])) {
            $simulcasting->setStreamKey($data['stream_key']);
        }

        $this->em->persist($simulcasting);
        $this->em->flush();

        $this->logger->info('Simulcasting stream updated', [
            'station_id' => $station->id,
            'simulcasting_id' => $simulcasting->getId(),
        ]);

        return $this->renderSuccess($request, $response, $simulcasting);
    }

    #[OA\Delete(
        path: '/station/{station_id}/simulcasting/{id}',
        operationId: 'deleteStationSimulcasting',
        summary: 'Delete a simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function deleteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $response, 'Simulcasting stream not found', [], 404);
        }

        $this->em->remove($simulcasting);
        $this->em->flush();

        $this->logger->info('Simulcasting stream deleted', [
            'station_id' => $station->id,
            'simulcasting_id' => $simulcastingId,
        ]);

        return $response->withStatus(204);
    }

    #[OA\Post(
        path: '/station/{station_id}/simulcasting/{id}/start',
        operationId: 'startStationSimulcasting',
        summary: 'Start a simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function startAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $response, 'Simulcasting stream not found', [], 404);
        }

        $backend = $this->adapters->getBackendAdapter($station);
        if (!$backend instanceof \App\Radio\Backend\Liquidsoap) {
            return $this->renderError($request, $response, 'Simulcasting only works with LiquidSoap backend', [], 400);
        }

        $success = $this->simulcastingManager->startSimulcasting($simulcasting, $backend);
        
        if ($success) {
            $this->em->persist($simulcasting);
            $this->em->flush();
            
            return $this->renderSuccess($request, $response, $simulcasting);
        } else {
            return $this->renderError($request, $response, 'Failed to start simulcasting stream', [], 500);
        }
    }

    #[OA\Post(
        path: '/station/{station_id}/simulcasting/{id}/stop',
        operationId: 'stopStationSimulcasting',
        summary: 'Stop a simulcasting stream',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Simulcasting stream ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function stopAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $station = $request->getStation();
        $simulcastingId = (int) $params['id'];

        $simulcasting = $this->em->find(Simulcasting::class, $simulcastingId);

        if (!$simulcasting || $simulcasting->getStation()->getId() !== $station->getId()) {
            return $this->renderError($request, $response, 'Simulcasting stream not found', [], 404);
        }

        $backend = $this->adapters->getBackendAdapter($station);
        if (!$backend instanceof \App\Radio\Backend\Liquidsoap) {
            return $this->renderError($request, $response, 'Simulcasting only works with LiquidSoap backend', [], 400);
        }

        $success = $this->simulcastingManager->stopSimulcasting($simulcasting, $backend);
        
        if ($success) {
            $this->em->persist($simulcasting);
            $this->em->flush();
            
            return $this->renderSuccess($request, $response, $simulcasting);
        } else {
            return $this->renderError($request, $response, 'Failed to stop simulcasting stream', [], 500);
        }
    }

    #[OA\Get(
        path: '/station/{station_id}/simulcasting/adapters',
        operationId: 'getStationSimulcastingAdapters',
        summary: 'Get available simulcasting adapters',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
        ]
    )]
    public function adaptersAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $adapters = $this->simulcastingManager->getAvailableAdapters();
        
        return $response->withJson($adapters);
    }

    private function getValidationConstraints(): array
    {
        return [
            'name' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Name is required',
                ]),
                new \Symfony\Component\Validator\Constraints\Length([
                    'min' => 1,
                    'max' => 255,
                    'minMessage' => 'Name must be at least {{ limit }} characters',
                    'maxMessage' => 'Name cannot exceed {{ limit }} characters',
                ]),
            ],
            'adapter' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Adapter is required',
                ]),
                new \Symfony\Component\Validator\Constraints\Length([
                    'max' => 50,
                    'maxMessage' => 'Adapter cannot exceed {{ limit }} characters',
                ]),
            ],
            'stream_key' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Stream key is required',
                ]),
                new \Symfony\Component\Validator\Constraints\Length([
                    'max' => 500,
                    'maxMessage' => 'Stream key cannot exceed {{ limit }} characters',
                ]),
            ],
        ];
    }
}

