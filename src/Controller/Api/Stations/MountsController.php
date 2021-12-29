<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSortResults;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow\UploadedFile;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractStationApiCrudController<Entity\StationMount> */
#[
    OA\Get(
        path: '/station/{station_id}/mounts',
        operationId: 'getStationMounts',
        description: 'List all current mount points.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/StationMount')
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/mounts',
        operationId: 'addMount',
        description: 'Create a new mount point.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationMount')
        ),
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationMount')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'getMount',
        description: 'Retrieve details for a single mount point.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Streamer ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationMount')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'editMount',
        description: 'Update details of a single mount point.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationMount')
        ),
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Streamer ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'deleteMount',
        description: 'Delete a single mount point.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'StationMount ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    )
]
class MountsController extends AbstractStationApiCrudController
{
    use CanSortResults;

    protected string $entityClass = Entity\StationMount::class;
    protected string $resourceRouteName = 'api:stations:mount';

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        protected Entity\Repository\StationMountRepository $mountRepo
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Entity\StationMount::class, 'e')
            ->where('e.station = :station')
            ->setParameter('station', $station);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'display_name'  => 'e.display_name',
                'enable_autodj' => 'e.enable_autodj',
            ],
            'e.display_name'
        );

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('(e.name LIKE :name OR e.display_name LIKE :name)')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        /** @var Entity\StationMount $record */
        $return = parent::viewRecord($record, $request);

        $station = $request->getStation();
        $frontend = $request->getStationFrontend();
        $router = $request->getRouter();

        $return['links']['intro'] = (string)$router->fromHere(
            route_name:   'api:stations:mounts:intro',
            route_params: ['id' => $record->getId()],
            absolute:     true
        );

        $return['links']['listen'] = (string)Router::resolveUri(
            $router->getBaseUrl(),
            $frontend->getUrlForMount($station, $record),
            true
        );

        return $return;
    }

    public function createAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $parsedBody = (array)$request->getParsedBody();
        $record = $this->editRecord(
            $parsedBody,
            new Entity\StationMount($station)
        );

        if (!empty($parsedBody['intro_file'])) {
            $intro = UploadedFile::fromArray($parsedBody['intro_file'], $station->getRadioTempDir());
            $this->mountRepo->setIntro($record, $intro);
        }

        return $response->withJson($this->viewRecord($record, $request));
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        mixed $station_id,
        mixed $id
    ): ResponseInterface {
        $record = $this->getRecord($this->getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->mountRepo->destroy($record);

        return $response->withJson(Entity\Api\Status::deleted());
    }

    /**
     * @inheritDoc
     */
    protected function getStation(ServerRequest $request): Entity\Station
    {
        $station = parent::getStation($request);

        $frontend = $request->getStationFrontend();
        if (!$frontend->supportsMounts()) {
            throw new StationUnsupportedException();
        }

        return $station;
    }
}
