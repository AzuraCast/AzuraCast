<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Repository\StationMountRepository;
use App\Entity\StationMount;
use App\Exception\ValidationException;
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
        summary: 'List all current mount points.',
        tags: [OpenApi::TAG_STATIONS_MOUNT_POINTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationMount::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/mounts',
        operationId: 'addMount',
        summary: 'Create a new mount point.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationMount::class)
        ),
        tags: [OpenApi::TAG_STATIONS_MOUNT_POINTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationMount::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'getMount',
        summary: 'Retrieve details for a single mount point.',
        tags: [OpenApi::TAG_STATIONS_MOUNT_POINTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Mount Point ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationMount::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/mount/{id}',
        operationId: 'editMount',
        summary: 'Update details of a single mount point.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationMount::class)
        ),
        tags: [OpenApi::TAG_STATIONS_MOUNT_POINTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Mount Point ID',
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
        path: '/station/{station_id}/mount/{id}',
        operationId: 'deleteMount',
        summary: 'Delete a single mount point.',
        tags: [OpenApi::TAG_STATIONS_MOUNT_POINTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Mount Point ID',
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
final class MountsController extends AbstractStationApiCrudController
{
    use CanSortResults;
    use CanSearchResults;

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

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.name',
                'e.display_name',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        $return = parent::viewRecord($record, $request);

        $station = $request->getStation();
        $router = $request->getRouter();

        $frontend = $this->adapters->getFrontendAdapter($station);

        $return['links']['intro'] = $router->fromHere(
            routeName: 'api:stations:mounts:intro',
            routeParams: ['id' => $record->id],
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
        $station = $request->getStation();
        if ($station->max_mounts !== 0 && $station->max_mounts <= $station->mounts->count()) {
            throw new ValidationException(
                __('Unable to create a new mount point, station\'s maximum mount points reached.')
            );
        }

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
