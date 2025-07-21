<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Api\PodcastEpisode as ApiPodcastEpisode;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow\UploadedFile;
use Doctrine\Common\Collections\Order;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<PodcastEpisode> */
#[
    OA\Get(
        path: '/station/{station_id}/podcast/{podcast_id}/episodes',
        operationId: 'getEpisodes',
        summary: 'List all current episodes for a given podcast ID.',
        tags: [OpenApi::TAG_STATIONS_PODCASTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'podcast_id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: ApiPodcastEpisode::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/podcast/{podcast_id}/episodes',
        operationId: 'addEpisode',
        summary: 'Create a new podcast episode.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiPodcastEpisode::class)
        ),
        tags: [OpenApi::TAG_STATIONS_PODCASTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'podcast_id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: ApiPodcastEpisode::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/podcast/{podcast_id}/episode/{id}',
        operationId: 'getEpisode',
        summary: 'Retrieve details for a single podcast episode.',
        tags: [OpenApi::TAG_STATIONS_PODCASTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'podcast_id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast Episode ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: ApiPodcastEpisode::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/podcast/{podcast_id}/episode/{id}',
        operationId: 'editEpisode',
        summary: 'Update details of a single podcast episode.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiPodcastEpisode::class)
        ),
        tags: [OpenApi::TAG_STATIONS_PODCASTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'podcast_id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast Episode ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
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
        path: '/station/{station_id}/podcast/{podcast_id}/episode/{id}',
        operationId: 'deleteEpisode',
        summary: 'Delete a single podcast episode.',
        tags: [OpenApi::TAG_STATIONS_PODCASTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'podcast_id',
                description: 'Podcast ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast Episode ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
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
class PodcastEpisodesController extends AbstractApiCrudController
{
    use CanSearchResults;
    use CanSortResults;

    protected string $entityClass = PodcastEpisode::class;
    protected string $resourceRouteName = 'api:stations:podcast:episode';

    public function __construct(
        protected readonly PodcastEpisodeRepository $episodeRepository,
        protected readonly PodcastEpisodeApiGenerator $episodeApiGen,
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
        $podcast = $request->getPodcast();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, p, pm')
            ->from(PodcastEpisode::class, 'e')
            ->join('e.podcast', 'p')
            ->leftJoin('e.media', 'pm')
            ->where('e.podcast = :podcast')
            ->setParameter('podcast', $podcast);

        $queryBuilder = $this->searchQueryBuilder(
            $request,
            $queryBuilder,
            [
                'e.title',
            ]
        );

        $queryBuilder = $this->sortQueryBuilder(
            $request,
            $queryBuilder,
            [
                'publish_at' => 'e.publish_at',
                'is_explicit' => 'e.is_explicit',
            ],
            'e.publish_at',
            Order::Descending
        );

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    /**
     * @return PodcastEpisode|null
     */
    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var string $id */
        $id = $params['episode_id'];

        return $this->episodeRepository->fetchEpisodeForPodcast(
            $request->getPodcast(),
            $id
        );
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();
        $podcast = $request->getPodcast();

        $record = $this->editRecord(
            $data,
            new PodcastEpisode($podcast)
        );

        if (!empty($data['artwork_file'])) {
            $artwork = UploadedFile::fromArray($data['artwork_file'], $station->getRadioTempDir());
            $this->episodeRepository->writeEpisodeArt(
                $record,
                $artwork->readAndDeleteUploadedFile()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        if (!empty($data['media_file'])) {
            $media = UploadedFile::fromArray($data['media_file'], $station->getRadioTempDir());

            $this->episodeRepository->uploadMedia(
                $record,
                $media->getClientFilename(),
                $media->getUploadedPath()
            );
        }

        return $record;
    }

    protected function deleteRecord(object $record): void
    {
        $this->episodeRepository->delete($record);
    }

    /**
     * @inheritDoc
     */
    protected function viewRecord(object $record, ServerRequest $request): ApiPodcastEpisode
    {
        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $return = $this->episodeApiGen->__invoke($record, $request);

        $baseRouteParams = [
            'station_id' => $request->getStation()->id,
            'podcast_id' => $record->podcast->id,
            'episode_id' => $record->id,
        ];

        $artRouteParams = $baseRouteParams;
        if (0 !== $return->art_updated_at) {
            $artRouteParams['timestamp'] = $return->art_updated_at;
        }

        $return->art = $router->named(
            routeName: 'api:stations:podcast:episode:art',
            routeParams: $artRouteParams,
            absolute: !$isInternal
        );

        $return->links = [
            ...$return->links,
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'art' => $router->named(
                routeName: 'api:stations:podcast:episode:art',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
            'media' => $router->fromHere(
                routeName: 'api:stations:podcast:episode:media',
                routeParams: $baseRouteParams,
                absolute: !$isInternal
            ),
        ];

        return $return;
    }
}
