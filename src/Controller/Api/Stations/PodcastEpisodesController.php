<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PodcastEpisodesController extends AbstractPodcastApiCrudController
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

        $paginator = Paginator::fromQueryBuilder($queryBuilder, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $is_internal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $postProcessor = function ($row) use ($is_bootgrid, $is_internal, $request, $router, $podcastMediaId) {
            $return = $this->viewRecord($row, $request);

            $return['links']['assign'] = (string)$router->fromHere(
                'api:stations:podcast:episode:media:assign',
                [
                    'episode_id' => $row->getId(),
                    'podcast_media_id' => $podcastMediaId,
                ]
            );

            // Older jQuery Bootgrid requests should be "flattened".
            if ($is_bootgrid && !$is_internal) {
                return Utilities\Arrays::flattenArray($return, '_');
            }

            return $return;
        };
        $paginator->setPostprocessor($postProcessor);
        return $paginator->write($response);
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

        $this->editRecord($this->getParsedBody($request), $podcast);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $podcast_id
    ): ResponseInterface {
        $record = $this->getRecord($request->getStation(), $podcast_id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $this->deleteRecord($record);

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
        $artwork = null;
        if (isset($data['artwork_file'])) {
            $artwork = $data['artwork_file'];
            unset($data['artwork_file']);
        }

        /** @var Entity\PodcastEpisode $record */
        $record = parent::fromArray($data, $record, $context);

        if ($artwork instanceof UploadedFileInterface && UPLOAD_ERR_OK === $artwork->getError()) {
            $this->episodeRepository->writeEpisodeArtwork(
                $record,
                $artwork->getStream()->getContents()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        return $record;
    }
}
