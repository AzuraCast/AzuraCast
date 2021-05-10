<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Stations\AbstractStationApiCrudController;
use App\Entity;
use App\Entity\Repository\PodcastCategoryRepository;
use App\Entity\Repository\StationPodcastRepository;
use App\Entity\Repository\StationRepository;
use App\Entity\StationPodcast;
use App\Entity\StationPodcastCategory;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PodcastsController extends AbstractStationApiCrudController
{
    protected string $entityClass = StationPodcast::class;
    protected string $resourceRouteName = 'api:stations:podcast';

    protected StationRepository $stationRepository;
    protected StationPodcastRepository $podcastRepository;
    protected PodcastCategoryRepository $categoryRepository;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        StationRepository $stationRepository,
        StationPodcastRepository $podcastRepository,
        PodcastCategoryRepository $categoryRepository
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->stationRepository = $stationRepository;
        $this->podcastRepository = $podcastRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritDoc
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p, pc, c')
            ->from(StationPodcast::class, 'p')
            ->leftJoin('p.categories', 'pc')
            ->leftJoin('pc.category', 'c')
            ->where('p.station = :station')
            ->orderBy('p.title', 'ASC')
            ->setParameter('station', $station);

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('p.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    public function getAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $station = $this->getStation($request);
        $router = $request->getRouter();

        /** @var StationPodcast $podcast */
        $podcast = $this->getRecord($station, $id);

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Podcast not found!')));
        }

        $return = $this->viewRecord($podcast, $request);

        $podcastArtworkSrc = (string) $this->stationRepository->getDefaultAlbumArtUrl($station);
        $return['has_custom_artwork'] = false;

        $podcastArtworkPath = $podcast->getArtworkPath($podcast->getUniqueId());

        if ($podcastsFilesystem->fileExists($podcastArtworkPath)) {
            $podcastArtworkSrc = (string) $router->named(
                'api:stations:podcast:art', [
                    'station_id' => $station->getId(),
                    'podcast_id' => $podcast->getId(),
                ],
                [],
                true
            );

            $return['has_custom_artwork'] = true;
        }

        $return['artwork_src'] = $podcastArtworkSrc;

        return $response->withJson($return);
    }

    public function getArtworkAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];

        $defaultArtRedirect = $response->withRedirect(
            (string) $this->stationRepository->getDefaultAlbumArtUrl($station),
            302
        );

        $podcastPath = '';

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast instanceof StationPodcast) {
            $podcastPath = $podcast->getArtworkPath($podcast->getUniqueId());
        } else {
            return $defaultArtRedirect;
        }

        $stationFilesystems = new StationFilesystems($station);
        $podcastsFilesystem = $stationFilesystems->getPodcastsFilesystem();

        if ($podcastsFilesystem->fileExists($podcastPath)) {
            return $response->streamFilesystemFile($podcastsFilesystem, $podcastPath, null, 'inline');
        }

        return $defaultArtRedirect;
    }

    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $this->getStation($request);

        $data = $request->getParsedBody();
        $categoryIds = $data['categories'] ?? [];

        $categories = $this->categoryRepository->fetchCategoriesByIds($categoryIds);

        $data['categories'] = $categories;

        /** @var StationPodcast $podcast */
        $podcast = $this->createRecord($data, $station);

        $files = $request->getUploadedFiles();

        if (!empty($files['artwork_file'])) {
            /** @var UploadedFileInterface $file */
            $file = $files['artwork_file'];

            if ($file->getError() === UPLOAD_ERR_OK) {
                $this->podcastRepository->writePodcastArtwork(
                    $podcast,
                    $file->getStream()->getContents()
                );
            }
        }

        $return = $this->viewRecord($podcast, $request);

        return $response->withJson($return);
    }

    public function editAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $podcast = $this->getRecord($this->getStation($request), $id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $station = $this->getStation($request);
        $data = $request->getParsedBody();

        $categoryIds = $data['categories'] ?? [];

        $categories = $this->categoryRepository->fetchCategoriesByIds($categoryIds);

        $data['categories'] = $categories;

        $this->editRecord($data, $podcast);

        $files = $request->getUploadedFiles();

        if (!empty($files['artwork_file'])) {
            /** @var UploadedFileInterface $file */
            $file = $files['artwork_file'];

            if ($file->getError() === UPLOAD_ERR_OK) {
                $this->podcastRepository->writePodcastArtwork(
                    $podcast,
                    $file->getStream()->getContents()
                );
            }
        }

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    public function clearPodcastArtworkAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $routeContext = RouteContext::fromRequest($request);
        $routeArgs = $routeContext->getRoute()->getArguments();
        $podcastId = (int) $routeArgs['podcast_id'];

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcastId);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Podcast not found!')));
        }

        $this->podcastRepository->removePodcastArtwork($podcast);

        $this->em->persist($podcast);
        $this->em->flush();

        return $response->withJson(new Entity\Api\Status(true, __('Podcast artwork successfully cleared.')));
    }

    protected function fromArray($data, $record = null, array $context = []): object
    {
        return parent::fromArray($data, $record, array_merge($context, [
            AbstractNormalizer::CALLBACKS => [
                'categories' => function (array $category, $record) {
                    if ($record instanceof StationPodcast) {
                        $categories = $record->getCategories();

                        if ($categories->count() > 0) {
                            foreach ($categories as $existingCategories) {
                                $this->em->remove($existingCategories);
                            }
                            $categories->clear();
                        }

                        foreach ($category as $category) {
                            $podcastCategory = new StationPodcastCategory($record, $category);
                            $this->em->persist($podcastCategory);
                            $categories->add($podcastCategory);
                        }
                    }
                    return null;
                },
            ],
        ]));
    }
}
