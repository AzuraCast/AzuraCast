<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\PodcastEpisode as ApiPodcastEpisode;
use App\Entity\Api\PodcastMedia as ApiPodcastMedia;
use App\Entity\PodcastEpisode;
use App\Entity\PodcastMedia;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\PodcastRepository;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow\UploadedFile;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<PodcastEpisode> */
#[
    OA\Get(
        path: '/station/{station_id}/podcast/{podcast_id}/episodes',
        operationId: 'getEpisodes',
        description: 'List all current episodes for a given podcast ID.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_PodcastEpisode')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/podcast/{podcast_id}/episodes',
        operationId: 'addEpisode',
        description: 'Create a new podcast episode.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_PodcastEpisode')
        ),
        tags: ['Stations: Podcasts'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_PodcastEpisode')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/podcast/{podcast_id}/episode/{id}',
        operationId: 'getEpisode',
        description: 'Retrieve details for a single podcast episode.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_PodcastEpisode')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/podcast/{podcast_id}/episode/{id}',
        operationId: 'editEpisode',
        description: 'Update details of a single podcast episode.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_PodcastEpisode')
        ),
        tags: ['Stations: Podcasts'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/podcast/{podcast_id}/episode/{id}',
        operationId: 'deleteEpisode',
        description: 'Delete a single podcast episode.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class PodcastEpisodesController extends AbstractApiCrudController
{
    protected string $entityClass = PodcastEpisode::class;
    protected string $resourceRouteName = 'api:stations:podcast:episode';

    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly PodcastEpisodeRepository $episodeRepository,
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
        /** @var string $podcastId */
        $podcastId = $params['podcast_id'];

        $station = $request->getStation();

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, p, pm')
            ->from(PodcastEpisode::class, 'e')
            ->join('e.podcast', 'p')
            ->leftJoin('e.media', 'pm')
            ->where('e.podcast = :podcast')
            ->orderBy('e.created_at', 'DESC')
            ->setParameter('podcast', $podcast);

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('e.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    /**
     * @return PodcastEpisode|null
     */
    protected function getRecord(ServerRequest $request, array $params): ?object
    {
        /** @var string $id */
        $id = $params['episode_id'];

        return $this->episodeRepository->fetchEpisodeForStation(
            $request->getStation(),
            $id
        );
    }

    protected function createRecord(ServerRequest $request, array $data): object
    {
        $station = $request->getStation();

        $podcastId = $request->getAttribute('podcast_id');

        $podcast = $this->podcastRepository->fetchPodcastForStation(
            $station,
            $podcastId
        );

        if (null === $podcast) {
            throw new RuntimeException('Podcast not found.');
        }

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
        if (!($record instanceof PodcastEpisode)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return = new ApiPodcastEpisode();
        $return->id = $record->getId();
        $return->title = $record->getTitle();
        $return->description = $record->getDescription();
        $return->explicit = $record->getExplicit();
        $return->publish_at = $record->getPublishAt();

        $mediaRow = $record->getMedia();
        $return->has_media = ($mediaRow instanceof PodcastMedia);
        if ($mediaRow instanceof PodcastMedia) {
            $media = new ApiPodcastMedia();
            $media->id = $mediaRow->getId();
            $media->original_name = $mediaRow->getOriginalName();
            $media->length = $mediaRow->getLength();
            $media->length_text = $mediaRow->getLengthText();
            $media->path = $mediaRow->getPath();

            $return->has_media = true;
            $return->media = $media;
        } else {
            $return->has_media = false;
            $return->media = new ApiPodcastMedia();
        }

        $return->art_updated_at = $record->getArtUpdatedAt();
        $return->has_custom_art = (0 !== $return->art_updated_at);

        $routeParams = [
            'episode_id' => $record->getId(),
        ];
        if ($return->has_custom_art) {
            $routeParams['timestamp'] = $return->art_updated_at;
        }

        $return->art = $router->fromHere(
            routeName: 'api:stations:podcast:episode:art',
            routeParams: $routeParams,
            absolute: true
        );

        $return->links = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'public' => $router->fromHere(
                routeName: 'public:podcast:episode',
                routeParams: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'download' => $router->fromHere(
                routeName: 'api:stations:podcast:episode:download',
                routeParams: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            ),
        ];

        $acl = $request->getAcl();
        $station = $request->getStation();

        if ($acl->isAllowed(StationPermissions::Podcasts, $station)) {
            $return->links['art'] = $router->fromHere(
                routeName: 'api:stations:podcast:episode:art-internal',
                routeParams: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            );
            $return->links['media'] = $router->fromHere(
                routeName: 'api:stations:podcast:episode:media-internal',
                routeParams: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            );
        }

        return $return;
    }
}
