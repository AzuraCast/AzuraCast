<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
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
        $record = $this->editRecord(
            $request->getParsedBody(),
            new Entity\PodcastEpisode($podcast)
        );

        $this->processFiles($request, $record);

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
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $this->editRecord($request->getParsedBody(), $podcast);
        $this->processFiles($request, $podcast);

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
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $fsStation = new StationFilesystems($station);
        $this->episodeRepository->delete($record, $fsStation->getPodcastsFilesystem());

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }

    /**
     * @param Entity\Station $station
     * @param string $id
     */
    protected function getRecord(Entity\Station $station, string $id): ?object
    {
        return $this->episodeRepository->fetchEpisodeForStation($station, $id);
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        /** @var Entity\PodcastEpisode $record */
        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['art'] = (string)$router->fromHere(
            'api:stations:podcast:episode:art',
            [
                'episode_id' => $record->getId() . '|' . $record->getArtUpdatedAt(),
            ]
        );

        $return['links'] = [
            'self' => $router->fromHere(
                $this->resourceRouteName,
                ['episode_id' => $record->getId()],
                [],
                !$isInternal
            ),
            'public' => (string)$router->fromHere(
                'public:podcast:episode',
                ['episode_id' => $record->getId()],
                [],
                !$isInternal
            ),
            'download' => (string)$router->fromHere(
                'api:stations:podcast:episode:download',
                ['episode_id' => $record->getId()],
                [],
                !$isInternal
            ),
        ];

        return $return;
    }

    protected function processFiles(
        ServerRequest $request,
        Entity\PodcastEpisode $record
    ): void {
        $files = $request->getUploadedFiles();

        $artwork = $files['artwork'] ?? null;
        if ($artwork instanceof UploadedFileInterface && UPLOAD_ERR_OK === $artwork->getError()) {
            $this->episodeRepository->writeEpisodeArt(
                $record,
                $artwork->getStream()->getContents()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        $media = $files['media'] ?? null;
        if ($media instanceof UploadedFileInterface && UPLOAD_ERR_OK === $media->getError()) {
            $fsStations = new StationFilesystems($request->getStation());
            $fsTemp = $fsStations->getTempFilesystem();

            $originalName = $media->getClientFilename() ?? $record->getId() . '.mp4';
            $originalExt = pathinfo($originalName, PATHINFO_EXTENSION);

            $tempPath = $fsTemp->getLocalPath($record->getId() . '.' . $originalExt);
            if ($media->moveTo($tempPath)) {
                $artwork = $this->podcastMediaRepository->upload(
                    $record,
                    $originalName,
                    $tempPath,
                    $fsStations->getPodcastsFilesystem()
                );

                if (0 === $record->getArtUpdatedAt()) {
                    $this->episodeRepository->writeEpisodeArt(
                        $record,
                        $artwork
                    );
                }

                $this->em->persist($record);
                $this->em->flush();
            }
        }
    }
}
