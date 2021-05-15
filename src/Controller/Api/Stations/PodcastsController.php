<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Acl;
use App\Entity;
use App\Entity\Podcast;
use App\Entity\PodcastCategory;
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

    /**
     * @param Entity\Station $station
     * @param int|string $id
     */
    protected function getRecord(Entity\Station $station, int|string $id): ?object
    {
        $repo = $this->em->getRepository($this->entityClass);
        return $repo->findOneBy(
            [
                'storage_location' => $station->getPodcastsStorageLocation(),
                'id' => $id,
            ]
        );
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $station = $request->getStation();

        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links'] = [
            'self' => $router->fromHere(
                $this->resourceRouteName,
                [
                    'podcast_id' => $record->getId(),
                ],
                [],
                !$isInternal
            ),
            'public-episodes' => $router->fromHere(
                'public:podcast:episodes',
                [
                    'podcast_id' => $record->getId(),
                ],
                [],
                !$isInternal
            ),
            'public-feed' => $router->fromHere(
                'public:podcast:feed',
                [
                    'podcast_id' => $record->getId(),
                ],
                [],
                !$isInternal
            ),
        ];

        $return['has_custom_artwork'] = (0 !== $record->getArtUpdatedAt());

        $return['art'] = (string)$router->named(
            'api:stations:podcast:art',
            [
                'station_id' => $station->getId(),
                'podcast_id' => $record->getId() . '-' . $record->getArtUpdatedAt(),
            ],
            [],
            true
        );

        $acl = $request->getAcl();

        if ($acl->isAllowed(Acl::STATION_PODCASTS, $station)) {
            $return['links']['station-episodes'] = (string)$router->named(
                'stations:podcast:episodes',
                [
                    'station_id' => $station->getId(),
                    'podcast_id' => $record->getId(),
                ]
            );

            $return['links']['art'] = (string)$router->named(
                'api:stations:podcast:art-internal',
                [
                    'station_id' => $station->getId(),
                    'podcast_id' => $record->getId(),
                ],
                [],
                true
            );
        }

        return $return;
    }

    public function createAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        $files = $request->getUploadedFiles();
        if (!empty($files['artwork_file'])) {
            $data['artwork_file'] = $files['artwork_file'];
        }

        $record = $this->createRecord($data, $request->getStation());

        return $response->withJson($this->viewRecord($record, $request));
    }

    /**
     * @param array $data
     * @param Entity\Station $station
     */
    protected function createRecord(array $data, Entity\Station $station): object
    {
        return $this->editRecord(
            $data,
            null,
            [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    $this->entityClass => [
                        'storageLocation' => $station->getPodcastsStorageLocation(),
                    ],
                ],
            ]
        );
    }

    public function editAction(ServerRequest $request, Response $response, $station_id, $id): ResponseInterface
    {
        $podcast = $this->getRecord($request->getStation(), $id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $data = $request->getParsedBody();

        $files = $request->getUploadedFiles();
        if (!empty($files['artwork_file'])) {
            $data['artwork_file'] = $files['artwork_file'];
        }

        $this->editRecord($data, $podcast);

        return $response->withJson(new Entity\Api\Status(true, __('Changes saved successfully.')));
    }

    protected function fromArray($data, $record = null, array $context = []): object
    {
        return parent::fromArray($data, $record, array_merge($context, [
            AbstractNormalizer::CALLBACKS => [
                'artwork_file' => function ($file, $record): void {
                    if ($file instanceof UploadedFileInterface && UPLOAD_ERR_OK === $file->getError()) {
                        $this->podcastRepository->writePodcastArtwork(
                            $record,
                            $file->getStream()->getContents()
                        );
                    }
                },
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
