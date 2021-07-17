<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Acl;
use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractApiCrudController<Entity\PodcastEpisode>
 */
class PodcastEpisodesController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\PodcastEpisode::class;
    protected string $resourceRouteName = 'api:stations:podcast:episode';

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        protected Entity\Repository\StationRepository $stationRepository,
        protected Entity\Repository\PodcastRepository $podcastRepository,
        protected Entity\Repository\PodcastMediaRepository $podcastMediaRepository,
        protected Entity\Repository\PodcastEpisodeRepository $episodeRepository
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @OA\Get(path="/station/{station_id}/podcast/{podcast_id}/episodes",
     *   tags={"Stations: Podcasts"},
     *   description="List all current episodes for a given podcast ID.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="podcast_id",
     *     in="path",
     *     description="Podcast ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_PodcastEpisode"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/podcast/{podcast_id}/episodes",
     *   tags={"Stations: Podcasts"},
     *   description="Create a new podcast episode.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="podcast_id",
     *     in="path",
     *     description="Podcast ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Api_PodcastEpisode")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_PodcastEpisode")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/podcast/{podcast_id}/episode/{id}",
     *   tags={"Stations: Podcasts"},
     *   description="Retrieve details for a single podcast episode.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="podcast_id",
     *     in="path",
     *     description="Podcast ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Podcast Episode ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_PodcastEpisode")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/podcast/{podcast_id}/episode/{id}",
     *   tags={"Stations: Podcasts"},
     *   description="Update details of a single podcast episode.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Api_PodcastEpisode")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="podcast_id",
     *     in="path",
     *     description="Podcast ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Podcast Episode ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/station/{station_id}/podcast/{podcast_id}/episode/{id}",
     *   tags={"Stations: Podcasts"},
     *   description="Delete a single podcast episode.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="podcast_id",
     *     in="path",
     *     description="Podcast ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Podcast Episode ID",
     *     required=true,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, p, pm')
            ->from(Entity\PodcastEpisode::class, 'e')
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

    public function getAction(
        ServerRequest $request,
        Response $response,
        string $episode_id
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->getRecord($station, $episode_id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $return = $this->viewRecord($record, $request);
        return $response->withJson($return);
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);
        if (null === $podcast) {
            throw new \RuntimeException('Podcast not found.');
        }

        $parsedBody = (array)$request->getParsedBody();

        $record = $this->editRecord(
            $parsedBody,
            new Entity\PodcastEpisode($podcast)
        );

        if (!empty($parsedBody['artwork_file'])) {
            $artwork = UploadedFile::fromArray($parsedBody['artwork_file'], $station->getRadioTempDir());
            $this->episodeRepository->writeEpisodeArt(
                $record,
                $artwork->readAndDeleteUploadedFile()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        if (!empty($parsedBody['media_file'])) {
            $media = UploadedFile::fromArray($parsedBody['media_file'], $station->getRadioTempDir());

            $this->podcastMediaRepository->upload(
                $record,
                $media->getOriginalFilename(),
                $media->getUploadedPath()
            );
        }

        return $response->withJson($this->viewRecord($record, $request));
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        string $episode_id
    ): ResponseInterface {
        $podcast = $this->getRecord($request->getStation(), $episode_id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->editRecord((array)$request->getParsedBody(), $podcast);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $episode_id
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->getRecord($station, $episode_id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $fsStation = new StationFilesystems($station);
        $this->episodeRepository->delete($record, $fsStation->getPodcastsFilesystem());

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }

    /**
     * @param Entity\Station $station
     * @param string $id
     *
     * @return Entity\PodcastEpisode|null
     */
    protected function getRecord(Entity\Station $station, string $id): ?object
    {
        return $this->episodeRepository->fetchEpisodeForStation($station, $id);
    }

    /**
     * @inheritDoc
     */
    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof Entity\PodcastEpisode)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return = new Entity\Api\PodcastEpisode();
        $return->id = $record->getId();
        $return->title = $record->getTitle();
        $return->description = $record->getDescription();
        $return->explicit = $record->getExplicit();
        $return->publish_at = $record->getPublishAt();

        $mediaRow = $record->getMedia();
        $return->has_media = ($mediaRow instanceof Entity\PodcastMedia);
        if ($mediaRow instanceof Entity\PodcastMedia) {
            $media = new Entity\Api\PodcastMedia();
            $media->id = $mediaRow->getId();
            $media->original_name = $mediaRow->getOriginalName();
            $media->length = $mediaRow->getLength();
            $media->length_text = $mediaRow->getLengthText();
            $media->path = $mediaRow->getPath();

            $return->has_media = true;
            $return->media = $media;
        } else {
            $return->has_media = false;
            $return->media = new Entity\Api\PodcastMedia();
        }

        $return->art_updated_at = $record->getArtUpdatedAt();
        $return->has_custom_art = (0 !== $return->art_updated_at);

        $return->art = (string)$router->fromHere(
            route_name: 'api:stations:podcast:episode:art',
            route_params: ['episode_id' => $record->getId() . '|' . $record->getArtUpdatedAt()],
            absolute: true
        );

        $return->links = [
            'self' => (string)$router->fromHere(
                route_name: $this->resourceRouteName,
                route_params: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'public' => (string)$router->fromHere(
                route_name: 'public:podcast:episode',
                route_params: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'download' => (string)$router->fromHere(
                route_name: 'api:stations:podcast:episode:download',
                route_params: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            ),
        ];

        $acl = $request->getAcl();
        $station = $request->getStation();

        if ($acl->isAllowed(Acl::STATION_PODCASTS, $station)) {
            $return->links['art'] = (string)$router->fromHere(
                route_name: 'api:stations:podcast:episode:art-internal',
                route_params: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            );
            $return->links['media'] = (string)$router->fromHere(
                route_name: 'api:stations:podcast:episode:media-internal',
                route_params: ['episode_id' => $record->getId()],
                absolute: !$isInternal
            );
        }

        return $return;
    }
}
