<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Repository\StationMountRepository;
use App\Entity\StationMount;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Adapters;
use App\Service\Flow\UploadedFile;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractStationApiCrudController<StationMount> */
#[
    OA\Get(
        path: '/station/{station_id}/mounts',
        operationId: 'getStationMounts',
        description: 'List all current mount points.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationMount')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'getMount',
        description: 'Retrieve details for a single mount point.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Streamer ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'deleteMount',
        description: 'Delete a single mount point.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Mount Points'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'StationMount ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class MountsController extends AbstractStationApiCrudController
{
    use CanSortResults;

    protected string $entityClass = StationMount::class;
    protected string $resourceRouteName = 'api:stations:mount';

    public function __construct(
        Serializer $serializer,
        ValidatorInterface $validator,
        private readonly StationMountRepository $mountRepo,
        private readonly Adapters $adapters,
    ) {
        parent::__construct($serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(StationMount::class, 'e')
            ->where('e.station = :station')
            ->setParameter('station', $station);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'display_name' => 'e.display_name',
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
        /** @var StationMount $record */
        $return = parent::viewRecord($record, $request);

        $station = $request->getStation();
        $router = $request->getRouter();

        $frontend = $this->adapters->getFrontendAdapter($station);

        $return['links']['intro'] = $router->fromHere(
            routeName: 'api:stations:mounts:intro',
            routeParams: ['id' => $record->getId()],
            absolute: true
        );

        if (null !== $frontend) {
            $return['links']['listen'] = (string)Router::resolveUri(
                $router->getBaseUrl(),
                $frontend->getUrlForMount($station, $record),
                true
            );
        }

        return $return;
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $record = parent::createRecord($request, $data);

        if (!empty($data['intro_file'])) {
            $station = $request->getStation();
            $intro = UploadedFile::fromArray($data['intro_file'], $station->getRadioTempDir());
            $this->mountRepo->setIntro($record, $intro);
        }

        return $record;
    }

    protected function deleteRecord(object $record): void
    {
        parent::deleteRecord($record);

        $this->mountRepo->destroy($record);
    }
}
