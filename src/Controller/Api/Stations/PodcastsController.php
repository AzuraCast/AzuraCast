<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\AbstractApiCrudController;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Enums\StationPermissions;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow\UploadedFile;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<Entity\Podcast> */
#[
    OA\Get(
        path: '/station/{station_id}/podcasts',
        operationId: 'getPodcasts',
        description: 'List all current podcasts.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_Podcast')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/podcasts',
        operationId: 'addPodcast',
        description: 'Create a new podcast.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
        ),
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/podcast/{id}',
        operationId: 'getPodcast',
        description: 'Retrieve details for a single podcast.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
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
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/podcast/{id}',
        operationId: 'editPodcast',
        description: 'Update details of a single podcast.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Podcast')
        ),
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast ID',
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
        path: '/station/{station_id}/podcast/{id}',
        operationId: 'deletePodcast',
        description: 'Delete a single podcast.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Podcasts'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Podcast ID',
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
final class PodcastsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\Podcast::class;
    protected string $resourceRouteName = 'api:stations:podcast';

    public function __construct(
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        private readonly Entity\Repository\PodcastRepository $podcastRepository
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
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
        string $station_id,
        string $podcast_id,
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->getRecord($station, $podcast_id);

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
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $parsedBody = (array)$request->getParsedBody();

        /** @var Entity\Podcast $record */
        $record = $this->editRecord(
            $parsedBody,
            new Entity\Podcast($station->getPodcastsStorageLocation())
        );

        if (!empty($parsedBody['artwork_file'])) {
            $artwork = UploadedFile::fromArray($parsedBody['artwork_file'], $station->getRadioTempDir());
            $this->podcastRepository->writePodcastArt(
                $record,
                $artwork->readAndDeleteUploadedFile()
            );

            $this->em->persist($record);
            $this->em->flush();
        }

        return $response->withJson($this->viewRecord($record, $request));
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $podcast_id
    ): ResponseInterface {
        $podcast = $this->getRecord($request->getStation(), $podcast_id);

        if ($podcast === null) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->editRecord((array)$request->getParsedBody(), $podcast);

        return $response->withJson(Entity\Api\Status::updated());
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $podcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->getRecord($station, $podcast_id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $fsStation = new StationFilesystems($station);
        $this->podcastRepository->delete($record, $fsStation->getPodcastsFilesystem());

        return $response->withJson(Entity\Api\Status::deleted());
    }

    /**
     * @param Entity\Station $station
     * @param string $id
     *
     * @return Entity\Podcast|null
     */
    private function getRecord(Entity\Station $station, string $id): ?object
    {
        return $this->podcastRepository->fetchPodcastForStation($station, $id);
    }

    protected function viewRecord(object $record, ServerRequest $request): Entity\Api\Podcast
    {
        if (!($record instanceof Entity\Podcast)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();
        $station = $request->getStation();

        $return = new Entity\Api\Podcast();
        $return->id = $record->getId();
        $return->storage_location_id = $record->getStorageLocation()->getId();
        $return->title = $record->getTitle();
        $return->link = $record->getLink();
        $return->description = $record->getDescription();
        $return->language = $record->getLanguage();
        $return->author = $record->getAuthor();
        $return->email = $record->getEmail();

        $categories = [];
        foreach ($record->getCategories() as $category) {
            $categories[] = $category->getCategory();
        }
        $return->categories = $categories;

        $episodes = [];
        foreach ($record->getEpisodes() as $episode) {
            $episodes[] = $episode->getId();
        }
        $return->episodes = $episodes;

        $return->has_custom_art = (0 !== $record->getArtUpdatedAt());
        $return->art = $router->fromHere(
            routeName: 'api:stations:podcast:art',
            routeParams: ['podcast_id' => $record->getId() . '|' . $record->getArtUpdatedAt()],
            absolute: true
        );

        $return->links = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'episodes' => $router->fromHere(
                routeName: 'api:stations:podcast:episodes',
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'public_episodes' => $router->fromHere(
                routeName: 'public:podcast:episodes',
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            ),
            'public_feed' => $router->fromHere(
                routeName: 'public:podcast:feed',
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            ),
        ];

        $acl = $request->getAcl();

        if ($acl->isAllowed(StationPermissions::Podcasts, $station)) {
            $return->links['art'] = $router->fromHere(
                routeName: 'api:stations:podcast:art-internal',
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            );

            $return->links['episode_new_art'] = $router->fromHere(
                routeName: 'api:stations:podcast:episodes:new-art',
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            );
            $return->links['episode_new_media'] = $router->fromHere(
                routeName: 'api:stations:podcast:episodes:new-media',
                routeParams: ['podcast_id' => $record->getId()],
                absolute: !$isInternal
            );
        }

        return $return;
    }

    /**
     * @param mixed[] $data
     * @param Entity\Podcast|null $record
     * @param array $context
     *
     * @return Entity\Podcast
     */
    protected function fromArray($data, $record = null, array $context = []): object
    {
        return parent::fromArray(
            $data,
            $record,
            array_merge(
                $context,
                [
                    AbstractNormalizer::CALLBACKS => [
                        'categories' => function (array $newCategories, $record): void {
                            if (!($record instanceof Entity\Podcast)) {
                                return;
                            }

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
                        },
                    ],
                ]
            )
        );
    }
}
