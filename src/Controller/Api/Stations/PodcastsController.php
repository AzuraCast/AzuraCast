<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Acl;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PodcastsController extends AbstractPodcastApiCrudController
{
    protected string $entityClass = Entity\Podcast::class;
    protected string $resourceRouteName = 'api:stations:podcast';

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        protected Entity\Repository\StationRepository $stationRepository,
        protected Entity\Repository\PodcastRepository $podcastRepository
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p, pc')
            ->from(Entity\Podcast::class, 'p')
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
        string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->getRecord($station, $podcast_id);

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
        string $podcast_id
    ): ResponseInterface {
        $podcast = $this->getRecord($request->getStation(), $podcast_id);

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
        return $this->podcastRepository->fetchPodcastForStation($station, $id);
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
            'public_episodes' => $router->fromHere(
                'public:podcast:episodes',
                [
                    'podcast_id' => $record->getId(),
                ],
                [],
                !$isInternal
            ),
            'public_feed' => $router->fromHere(
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
                'podcast_id' => $record->getId() . '|' . $record->getArtUpdatedAt(),
            ],
            [],
            true
        );
        $return['has_custom_art'] = (0 !== $record->getArtUpdatedAt());

        $acl = $request->getAcl();

        if ($acl->isAllowed(Acl::STATION_PODCASTS, $station)) {
            $return['links']['station_episodes'] = (string)$router->named(
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

    protected function fromArray($data, $record = null, array $context = []): object
    {
        $artwork = null;
        if (isset($data['artwork_file'])) {
            $artwork = $data['artwork_file'];
            unset($data['artwork_file']);
        }

        /** @var Entity\Podcast $record */
        $record = parent::fromArray(
            $data,
            $record,
            array_merge(
                $context,
                [
                    AbstractNormalizer::CALLBACKS => [
                        'categories' => function (array $newCategories, $record) {
                            if ($record instanceof Entity\Podcast) {
                                $categories = $record->getCategories();
                                if ($categories->count() > 0) {
                                    foreach ($categories as $existingCategories) {
                                        $this->em->remove($existingCategories);
                                    }
                                    $categories->clear();
                                }

                                foreach ($newCategories as $category) {
                                    $podcastCategory = new Entity\PodcastCategory($record, $category);
                                    $this->em->persist($podcastCategory);
                                    $categories->add($podcastCategory);
                                }
                            }
                            return null;
                        },
                    ],
                ]
            )
        );

        if ($artwork instanceof UploadedFileInterface && UPLOAD_ERR_OK === $artwork->getError()) {
            $this->podcastRepository->writePodcastArtwork(
                $record,
                $artwork->getStream()->getContents()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        return $record;
    }
}
