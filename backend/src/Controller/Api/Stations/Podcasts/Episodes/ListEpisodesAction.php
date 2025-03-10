<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts\Episodes;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\SingleActionInterface;
use App\Entity\Api\PodcastEpisode as ApiPodcastEpisode;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\Enums\PodcastSources;
use App\Entity\PodcastEpisode;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/public/podcast/{podcast_id}/episodes',
    operationId: 'getStationPublicPodcastEpisodes',
    summary: 'List all visible episodes for a given podcast.',
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
                    ref: ApiPodcastEpisode::class
                )
            )
        ),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ListEpisodesAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use CanSearchResults;

    public function __construct(
        private readonly PodcastEpisodeApiGenerator $episodeApiGen
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $podcast = $request->getPodcast();

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('e, p, pm')
            ->from(PodcastEpisode::class, 'e')
            ->join('e.podcast', 'p')
            ->leftJoin('e.media', 'pm')
            ->leftJoin('e.playlist_media', 'sm')
            ->where('e.podcast = :podcast')
            ->setParameter('podcast', $podcast)
            ->andWhere('e.publish_at <= :publishTime')
            ->setParameter('publishTime', time())
            ->andWhere(
                '(p.source = :sourceManual AND pm.id IS NOT NULL) OR (p.source = :sourcePlaylist AND sm.id IS NOT NULL)'
            )
            ->setParameter('sourceManual', PodcastSources::Manual->value)
            ->setParameter('sourcePlaylist', PodcastSources::Playlist->value)
            ->orderBy('e.publish_at', 'DESC');

        $queryBuilder = $this->searchQueryBuilder(
            $request,
            $queryBuilder,
            [
                'e.title',
            ]
        );

        $paginator = Paginator::fromQueryBuilder($queryBuilder, $request);
        $paginator->setPostprocessor(fn($row) => $this->episodeApiGen->__invoke($row, $request));

        return $paginator->write($response);
    }
}
