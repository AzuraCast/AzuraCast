<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Podcast as ApiPodcast;
use App\Entity\ApiGenerator\PodcastApiGenerator;
use App\Entity\Podcast;
use App\Entity\Repository\PodcastRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/public/podcasts',
    operationId: 'getStationPublicPodcasts',
    summary: 'List all visible public podcasts.',
    security: [],
    tags: [OpenApi::TAG_PUBLIC_STATIONS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'podcast_id',
            description: 'Podcast ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: ApiPodcast::class
                )
            )
        ),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ListPodcastsAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use CanSearchResults;

    public function __construct(
        private readonly PodcastApiGenerator $podcastApiGen,
        private readonly PodcastRepository $podcastRepo
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $station = $request->getStation();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p, pc')
            ->from(Podcast::class, 'p')
            ->leftJoin('p.categories', 'pc')
            ->where('p.storage_location = :storageLocation')
            ->andWhere('p.is_enabled = 1')
            ->setParameter('storageLocation', $station->podcasts_storage_location)
            ->andWhere('p.id IN (:podcastIds)')
            ->setParameter('podcastIds', $this->podcastRepo->getPodcastIdsWithPublishedEpisodes($station))
            ->orderBy('p.title', 'ASC');

        $queryBuilder = $this->searchQueryBuilder(
            $request,
            $queryBuilder,
            [
                'p.title',
            ]
        );

        $paginator = Paginator::fromQueryBuilder($queryBuilder, $request);
        $paginator->setPostprocessor(fn($row) => $this->podcastApiGen->__invoke($row, $request));

        return $paginator->write($response);
    }
}
