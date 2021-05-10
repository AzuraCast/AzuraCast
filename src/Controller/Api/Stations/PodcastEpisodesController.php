<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Entity\Repository\StationPodcastEpisodeRepository;
use App\Entity\Repository\StationPodcastMediaRepository;
use App\Entity\Repository\StationPodcastRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\StationPodcastEpisode;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Routing\RouteContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PodcastEpisodesController extends AbstractStationApiCrudController
{
    protected string $entityClass = StationPodcastEpisode::class;
    protected string $resourceRouteName = 'api:stations:episode';

    protected StationRepository $stationRepository;
    protected StationPodcastRepository $podcastRepository;
    protected StationPodcastMediaRepository $podcastMediaRepository;
    protected StationPodcastEpisodeRepository $episodeRepository;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        StationRepository $stationRepository,
        StationPodcastRepository $podcastRepository,
        StationPodcastMediaRepository $podcastMediaRepository,
        StationPodcastEpisodeRepository $episodeRepository
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->stationRepository = $stationRepository;
        $this->podcastRepository = $podcastRepository;
        $this->podcastMediaRepository = $podcastMediaRepository;
        $this->episodeRepository = $episodeRepository;
    }

    /**
     * @inheritDoc
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $router = $request->getRouter();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, s, p, pm')
            ->from(StationPodcastEpisode::class, 'e')
            ->join('e.station', 's')
            ->join('e.podcast', 'p')
            ->leftJoin('e.podcastMedia', 'pm')
            ->where('e.podcast = :podcast')
            ->orderBy('e.title', 'ASC')
            ->setParameter('podcast', $podcast);

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('e.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        $paginator = Paginator::fromQueryBuilder($queryBuilder, $request);

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $paginator->setPostprocessor(
            function (StationPodcastEpisode $episode) use ($station, $podcastsFilesystem, $router) {
                $episodeArtworkSrc = (string) $this->stationRepository->getDefaultAlbumArtUrl($station);

                if ($podcastsFilesystem->fileExists($episode->getArtworkPath($episode->getUniqueId()))) {
                    $episodeArtworkSrc = (string) $router->named('api:stations:episode:art', [
                        'station_id' => $station->getId(),
                        'podcast_id' => $episode->getPodcast()->getId(),
                        'episode_id' => $episode->getId(),
                    ]);
                }

                $episodeData = [
                    'id' => $episode->getId(),
                    'unique_id' => $episode->getUniqueId(),
                    'title' => $episode->getTitle(),
                    'description' => $episode->getDescription(),
                    'explicit' => $episode->getExplicit(),
                    'artwork_src' => $episodeArtworkSrc,
                    'publish_at' => $episode->getPublishAt(),
                    'has_media' => ($episode->getPodcastMedia() !== null),
                    'podcast_media' => null,
                    'links' => [
                        'self' => $router->fromHere($this->resourceRouteName, ['id' => $episode->getId()]),
                    ],
                ];

                $podcastMedia = $episode->getPodcastMedia();

                if ($podcastMedia !== null) {
                    $episodeData['podcast_media'] = [
                        'id' => $podcastMedia->getId(),
                        'unique_id' => $podcastMedia->getUniqueId(),
                        'original_name' => $podcastMedia->getOriginalName(),
                        'length' => $podcastMedia->getLength(),
                        'length_text' => $podcastMedia->getLengthText(),
                        'path' => $podcastMedia->getPath(),
                    ];
                }

                return $episodeData;
            }
        );

        return $paginator->write($response);
    }

    public function listAssignableAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $router = $request->getRouter();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];
        $podcastMediaId = (int) $request->getQueryParam('podcast_media_id', 0);

        $podcastMedia = $this->podcastMediaRepository->fetchPodcastMediaForStation(
            $station,
            $podcastMediaId
        );

        if ($podcastMedia === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Podcast media not found!')));
        }

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, s, p, pm')
            ->from(StationPodcastEpisode::class, 'e')
            ->join('e.station', 's')
            ->join('e.podcast', 'p')
            ->leftJoin('e.podcastMedia', 'pm')
            ->where('e.podcast = :podcast')
            ->orderBy('e.title', 'ASC')
            ->setParameter('podcast', $podcast);

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('e.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        $paginator = Paginator::fromQueryBuilder($queryBuilder, $request);

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        $paginator->setPostprocessor(
            function (StationPodcastEpisode $episode) use ($station, $podcastsFilesystem, $router, $podcastMediaId) {
                $episodeArtworkSrc = (string) $this->stationRepository->getDefaultAlbumArtUrl($station);

                if ($podcastsFilesystem->fileExists($episode->getArtworkPath($episode->getUniqueId()))) {
                    $episodeArtworkSrc = (string) $router->named('api:stations:episode:art', [
                        'station_id' => $station->getId(),
                        'podcast_id' => $episode->getPodcast()->getId(),
                        'episode_id' => $episode->getId(),
                    ]);
                }

                $assignPodcastMediaActionUrl = (string) $router->named('api:stations:episode:media:assign', [
                    'station_id' => $station->getId(),
                    'episode_id' => $episode->getId(),
                    'podcast_media_id' => $podcastMediaId
                ]);

                $episodeData = [
                    'id' => $episode->getId(),
                    'unique_id' => $episode->getUniqueId(),
                    'title' => $episode->getTitle(),
                    'description' => $episode->getDescription(),
                    'explicit' => $episode->getExplicit(),
                    'artwork_src' => $episodeArtworkSrc,
                    'publish_at' => $episode->getPublishAt(),
                    'has_media' => ($episode->getPodcastMedia() !== null),
                    'podcast_media' => null,
                    'links' => [
                        'self' => $router->fromHere($this->resourceRouteName, ['id' => $episode->getId()]),
                        'assign' => $assignPodcastMediaActionUrl,
                    ],
                ];

                $episodePodcastMedia = $episode->getPodcastMedia();

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

                return $episodeData;
            }
        );

        return $paginator->write($response);
    }

    public function getAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $station = $this->getStation($request);
        $router = $request->getRouter();

        /** @var StationPodcastEpisode $episode */
        $episode = $this->getRecord($station, $id);

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Episode not found!')));
        }

        $return = $this->viewRecord($episode, $request);

        $episodeArtworkSrc = (string) $this->stationRepository->getDefaultAlbumArtUrl($station);
        $return['has_custom_artwork'] = false;

        if ($podcastsFilesystem->fileExists($episode->getArtworkPath($episode->getUniqueId()))) {
            $episodeArtworkSrc = (string) $router->named('api:stations:episode:art', [
                'station_id' => $station->getId(),
                'podcast_id' => $episode->getPodcast()->getId(),
                'episode_id' => $episode->getId(),
            ], [], true);

            $return['has_custom_artwork'] = true;
        }

        $return['artwork_src'] = $episodeArtworkSrc;

        return $response->withJson($return);
    }

    public function getArtworkAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $episodeId = (int) $routeArgs['episode_id'];

        $defaultArtRedirect = $response->withRedirect((string) $this->stationRepository->getDefaultAlbumArtUrl($station), 302);

        $episodePath = '';

        $episode = $this->episodeRepository->fetchEpisodeForStation($station, $episodeId);

        if ($episode instanceof StationPodcastEpisode) {
            $episodePath = $episode->getArtworkPath($episode->getUniqueId());
        } else {
            return $defaultArtRedirect;
        }

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        if ($podcastsFilesystem->fileExists($episodePath)) {
            return $response->streamFilesystemFile($podcastsFilesystem, $episodePath, null, 'inline');
        }

        return $defaultArtRedirect;
    }

    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->getStation($request);

        /** @var StationPodcastEpisode $episode */
        $episode = $this->createRecord($request->getParsedBody(), $station);

        $files = $request->getUploadedFiles();

        if (!empty($files['artwork_file'])) {
            /** @var UploadedFileInterface $file */
            $file = $files['artwork_file'];

            if ($file->getError() === UPLOAD_ERR_OK) {
                $this->episodeRepository->writeEpisodeArtwork(
                    $episode,
                    $file->getStream()->getContents()
                );
            }
        }

        $return = $this->viewRecord($episode, $request);

        return $response->withJson($return);
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

        $this->editRecord($data, $episode);

        $files = $request->getUploadedFiles();

        if (!empty($files['artwork_file'])) {
            /** @var UploadedFileInterface $file */
            $file = $files['artwork_file'];

            if ($file->getError() === UPLOAD_ERR_OK) {
                $this->episodeRepository->writeEpisodeArtwork(
                    $episode,
                    $file->getStream()->getContents()
                );
            }
        }

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    public function clearEpisodeArtworkAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $episodeId = (int) $routeArgs['episode_id'];

        $episode = $this->episodeRepository->fetchEpisodeForStation($station, $episodeId);

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Episode not found!')));
        }

        $this->episodeRepository->removeEpisodeArt($episode);

        $this->em->persist($episode);
        $this->em->flush();

        return $response->withJson(new Entity\Api\Status(true, __('Episode artwork successfully cleared.')));
    }

    public function assignPodcastMediaAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $episodeId = (int) $routeArgs['episode_id'];
        $podcastMediaId = (int) $routeArgs['podcast_media_id'];

        $episode = $this->episodeRepository->fetchEpisodeForStation($station, $episodeId);
        $podcastMedia = $this->podcastMediaRepository->fetchPodcastMediaForStation($station, $podcastMediaId);

        if ($episode === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Episode not found!')));
        }

        if ($podcastMedia === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Podcast media not found!')));
        }

        $podcastMedia->setEpisode($episode);
        $this->em->persist($podcastMedia);
        $this->em->flush();

        return $response->withJson(new Entity\Api\Status(true, __('Podcast media successfully assigned to episode.')));
    }

    /**
     * @param array $data
     */
    protected function createRecord($data, Entity\Station $station): object
    {
        $data = $this->normalizeFormDataBooleans($data);

        $podcast = $this->podcastRepository->fetchPodcastForStation(
            $station,
            (int) $data['podcast_id']
        );

        return $this->editRecord($data, null, [
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                $this->entityClass => [
                    'station' => $station,
                    'podcast' => $podcast,
                ],
            ],
        ]);
    }

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
}
