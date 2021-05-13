<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Entity\Podcast;
use App\Entity\PodcastCategory;
use App\Entity\Repository\PodcastRepository;
use App\Entity\Repository\StationRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PodcastsController extends AbstractStationApiCrudController
{
    protected string $entityClass = Podcast::class;
    protected string $resourceRouteName = 'api:stations:podcast';

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        protected StationRepository $stationRepository,
        protected PodcastRepository $podcastRepository
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @inheritDoc
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p, pc')
            ->from(Podcast::class, 'p')
            ->leftJoin('p.categories', 'pc')
            ->where('p.storage_location = :storageLocation')
            ->orderBy('p.title', 'ASC')
            ->setParameter('storageLocation', $station->getPodcastsStorageLocation());

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $queryBuilder->andWhere('p.title LIKE :title')
                ->setParameter('title', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $queryBuilder->getQuery());
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        $station_id,
        $id
    ): ResponseInterface {
        $station = $this->getStation($request);
        $router = $request->getRouter();

        /** @var Podcast $podcast */
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
                'api:stations:podcast:art',
                [
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

    public function getArtworkAction(
        ServerRequest $request,
        Response $response,
        int $podcast_id,
    ): ResponseInterface {
        $station = $request->getStation();

        $defaultArtRedirect = $response->withRedirect(
            (string)$this->stationRepository->getDefaultAlbumArtUrl($station),
            302
        );

        $podcastPath = '';

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

        if ($podcast instanceof Podcast) {
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

        /** @var Podcast $podcast */
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
        $station = $this->getStation($request);
        $podcast = $this->getRecord($station, $id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $data = $request->getParsedBody();
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

    public function clearPodcastArtworkAction(
        ServerRequest $request,
        Response $response,
        int $podcast_id,
    ): ResponseInterface {
        $station = $request->getStation();

        $podcast = $this->podcastRepository->fetchPodcastForStation($station, $podcast_id);

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
                'categories' => function (array $newCategories, $record) {
                    if ($record instanceof Podcast) {
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
                    return null;
                },
            ],
        ]));
    }
}
