<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Acl;
use App\Entity;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Entity\Repository\PodcastMediaRepository;
use App\Entity\Repository\PodcastRepository;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PodcastEpisodesController extends AbstractStationApiCrudController
{
    protected string $entityClass = PodcastEpisode::class;
    protected string $resourceRouteName = 'api:stations:episode';

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        protected StationRepository $stationRepository,
        protected PodcastRepository $podcastRepository,
        protected PodcastMediaRepository $podcastMediaRepository,
        protected PodcastEpisodeRepository $episodeRepository
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @inheritDoc
     */
    public function listAction(
        ServerRequest $request,
        Response $response,
    ): ResponseInterface {
        $station = $request->getStation();

        $podcastId = $request->getRouteArgument('podcast_id');
        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, p, pm')
            ->from(PodcastEpisode::class, 'e')
            ->join('e.podcast', 'p')
            ->leftJoin('e.media', 'pm')
            ->where('e.podcast = :podcast')
            ->orderBy('e.title', 'ASC')
            ->setParameter('podcast', $podcast);

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('e.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    public function listAssignableAction(
        ServerRequest $request,
        Response $response,
        string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();

        $podcastMediaId = $request->getQueryParam('podcast_media_id', 0);
        $podcastMedia = $this->podcastMediaRepository->fetchPodcastMediaForStation(
            $station,
            $podcastMediaId
        );

        if ($podcastMedia === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Podcast media not found!')));
        }

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, p, pm')
            ->from(PodcastEpisode::class, 'e')
            ->join('e.podcast', 'p')
            ->leftJoin('e.media', 'pm')
            ->where('e.podcast = :podcast')
            ->orderBy('e.title', 'ASC')
            ->setParameter('podcast', $podcast);

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('e.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        /** @var PodcastEpisode $record */
        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();
        $station = $request->getStation();

        $return['art'] = (string)$router->named(
            'api:stations:podcast:episode:art',
            [
                'station_id' => $station->getId(),
                'podcast_id' => $record->getPodcast()->getId(),
                'episode_id' => $record->getId() . '-' . $record->getArtUpdatedAt(),
            ]
        );

        $return['links'] = [
            'self' => $router->fromHere(
                $this->resourceRouteName,
                ['episode_id' => $record->getId()],
                [],
                !$isInternal
            ),
            'public' => (string)$router->named(
                'public:podcast:episode',
                [
                    'station_id' => $station->getId(),
                    'podcast_id' => $record->getPodcast()->getId(),
                    'episode_id' => $record->getId(),
                ],
                [],
                !$isInternal
            ),
            'download' => (string)$router->named(
                'api:stations:podcast:episode:download',
                [
                    'station_id' => $station->getId(),
                    'podcast_id' => $record->getPodcast()->getId(),
                    'episode_id' => $record->getId(),
                ],
                [],
                !$isInternal
            ),
        ];

        $acl = $request->getAcl();

        if ($acl->isAllowed(Acl::STATION_PODCASTS, $station)) {
            $return['links']['assign'] = (string)$router->named(
                'api:stations:episode:media:assign',
                [
                    'station_id' => $station->getId(),
                    'episode_id' => $record->getId(),
                    'podcast_media_id' => $record->getMedia()?->getId(),
                ]
            );
        }

        /*
        $episodeData = [
            'id' => $episode->getId(),
            'unique_id' => $episode->getUniqueId(),
            'title' => $episode->getTitle(),
            'description' => $episode->getDescription(),
            'explicit' => $episode->getExplicit(),
            'artwork_src' => $episodeArtworkSrc,
            'publish_at' => $episode->getPublishAt(),
            'has_media' => ($episode->getMedia() !== null),
            'podcast_media' => null,
        ];

        $episodePodcastMedia = $episode->getMedia();

        if ($episodePodcastMedia !== null) {
            $episodeData['podcast_media'] = [
                'id' => $episodePodcastMedia->getId(),
                'unique_id' => $episodePodcastMedia->getUniqueId(),
                'original_name' => $episodePodcastMedia->getOriginalName(),
                'length' => $episodePodcastMedia->getLength(),
                'length_text' => $episodePodcastMedia->getLengthText(),
                'path' => $episodePodcastMedia->getPath(),
            ];
        }
        */

        return $return;
    }

    public function getAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $station = $request->getStation();
        $episode = $this->getRecord($station, $id);

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Episode not found!')));
        }

        return $response->withJson($this->viewRecord($episode, $request));
    }

    protected function getRecord(Entity\Station $station, int|string $id): ?object
    {
        return $this->episodeRepository->fetchEpisodeForStation($station, $id);
    }

    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->getStation($request);

        $data = $this->normalizeFormDataBooleans($request->getParsedBody());

        $files = $request->getUploadedFiles();
        if (!empty($files['artwork_file'])) {
            $data['artwork_file'] = $files['artwork_file'];
        }

        /** @var PodcastEpisode $episode */
        $episode = $this->createRecord($data, $station);

        return $response->withJson($this->viewRecord($episode, $request));
    }

    /**
     * @inheritDoc
     */
    public function editAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $episode = $this->getRecord($this->getStation($request), $id);

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $data = $this->normalizeFormDataBooleans($request->getParsedBody());

        $files = $request->getUploadedFiles();
        if (!empty($files['artwork_file'])) {
            $data['artwork_file'] = $files['artwork_file'];
        }

        $this->editRecord($data, $episode);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    /**
     * @param mixed[] $data
     */
    protected function createRecord($data, Entity\Station $station): object
    {
        $data = $this->normalizeFormDataBooleans($data);

        $podcast = $this->podcastRepository->fetchPodcastForStation(
            $station,
            $data['podcast_id']
        );

        return $this->editRecord($data, null, [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                $this->entityClass => [
                    'podcast' => $podcast,
                ],
            ],
        ]);
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    protected function normalizeFormDataBooleans(array $data): array
    {
        // FormData only sends string values so booleans aren't correctly evaluated, this fixed that
        foreach ($data as $dataKey => $dataValue) {
            if ($dataValue === 'false' || $dataValue === 'true') {
                $data[$dataKey] = ($dataValue === 'true');
            }
        }

        return $data;
    }

    protected function fromArray($data, $record = null, array $context = []): object
    {
        return parent::fromArray(
            $data,
            $record,
            array_merge(
                $context,
                [
                    AbstractNormalizer::CALLBACKS => [
                        'artwork_file' => function ($file, $record): void {
                            if ($file instanceof UploadedFileInterface && UPLOAD_ERR_OK === $file->getError()) {
                                $this->episodeRepository->writeEpisodeArtwork(
                                    $record,
                                    $file->getStream()->getContents()
                                );
                            }
                        },
                    ],
                ]
            )
        );
    }
}
