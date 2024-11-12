<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSearchResults;
use App\Entity\Api\Podcast as ApiPodcast;
use App\Entity\ApiGenerator\PodcastApiGenerator;
use App\Entity\Podcast;
use App\Entity\PodcastCategory;
use App\Entity\Repository\PodcastRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow\UploadedFile;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<Podcast> */
#[
    OA\Get(
        path: '/station/{station_id}/podcasts',
        operationId: 'getPodcasts',
        description: 'List all current podcasts.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_Podcast')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/podcasts',
        operationId: 'addPodcast',
        description: 'Create a new podcast.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
        ),
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/podcast/{id}',
        operationId: 'getPodcast',
        description: 'Retrieve details for a single podcast.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/podcast/{id}',
        operationId: 'editPodcast',
        description: 'Update details of a single podcast.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
        ),
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
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
        path: '/station/{station_id}/podcast/{id}',
        operationId: 'deletePodcast',
        description: 'Delete a single podcast.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
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
final class PodcastsController extends AbstractApiCrudController
{
    use CanSearchResults;

    protected string $entityClass = Podcast::class;
    protected string $resourceRouteName = 'api:stations:podcast';

    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly PodcastApiGenerator $podcastApiGen,
        Serializer $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p, pc')
            ->from(Podcast::class, 'p')
            ->leftJoin('p.categories', 'pc')
            ->where('p.storage_location = :storageLocation')
            ->orderBy('p.title', 'ASC')
            ->setParameter('storageLocation', $station->getPodcastsStorageLocation());

        $queryBuilder = $this->searchQueryBuilder(
            $request,
            $queryBuilder,
            [
                'p.title',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var string $id */
        $id = $params['podcast_id'];

        return $this->podcastRepository->fetchPodcastForStation(
            $request->getStation(),
            $id
        );
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();

        /** @var Podcast $record */
        $record = $this->editRecord(
            $data,
            new Podcast($station->getPodcastsStorageLocation())
        );

        if (!empty($data['artwork_file'])) {
            $artwork = UploadedFile::fromArray($data['artwork_file'], $station->getRadioTempDir());
            $this->podcastRepository->writePodcastArt(
                $record,
                $artwork->readAndDeleteUploadedFile()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        return $record;
    }

    protected function deleteRecord(object $record): void
    {
        $this->podcastRepository->delete($record);
    }

    protected function viewRecord(object $record, ServerRequest $request): ApiPodcast
    {
        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $return = $this->podcastApiGen->__invoke($record, $request);

        $baseRouteParams = [
            'station_id' => $request->getStation()->getIdRequired(),
            'podcast_id' => $record->getIdRequired(),
        ];

        $artRouteParams = $baseRouteParams;
        if (0 !== $return->art_updated_at) {
            $artRouteParams['timestamp'] = $return->art_updated_at;
        }

        $return->art = $router->named(
            routeName: 'api:stations:podcast:art',
            routeParams: $artRouteParams,
            absolute: !$isInternal
        );

        $return->links = [
            ...$return->links,
            'self' => $router->named(
                routeName: $this->resourceRouteName,
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'art' => $router->named(
                routeName: 'api:stations:podcast:art',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'episodes' => $router->named(
                routeName: 'api:stations:podcast:episodes',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'episode_new_art' => $router->named(
                routeName: 'api:stations:podcast:episodes:new-art',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'episode_new_media' => $router->named(
                routeName: 'api:stations:podcast:episodes:new-media',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'batch' => $router->named(
                routeName: 'api:stations:podcast:batch',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
        ];

        return $return;
    }

    /**
     * @param mixed[] $data
     * @param Podcast|null $record
     * @param array $context
     *
     * @return Podcast
     */
    protected function fromArray($data, $record = null, array $context = []): object
    {
        /** @var null|string[] $newCategories */
        $newCategories = null;
        if (isset($data['categories'])) {
            $newCategories = Types::arrayOrNull($data['categories']);
            unset($data['categories']);
        }

        if (isset($data['playlist_id'])) {
            $data['playlist'] = $data['playlist_id'];
            unset($data['playlist_id']);
        }

        $record = parent::fromArray($data, $record, $context);

        if (null !== $newCategories) {
            $categories = $record->getCategories();
            if ($categories->count() > 0) {
                foreach ($categories as $existingCategories) {
                    $this->em->remove($existingCategories);
                }
                $categories->clear();
            }

            foreach ($newCategories as $category) {
                $podcastCategory = new PodcastCategory($record, $category);
                $this->em->persist($podcastCategory);

                $categories->add($podcastCategory);
            }
        }

        return $record;
    }
}
