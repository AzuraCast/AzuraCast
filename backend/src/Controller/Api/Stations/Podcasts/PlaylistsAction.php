<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistSources;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/podcast/{podcast_id}/playlists',
    operationId: 'getStationPodcastPlaylists',
    description: 'Get a list of playlists that can be associated with a podcast.',
    tags: [OpenApi::TAG_STATIONS_PODCASTS],
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
        // TODO API Response
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class PlaylistsAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $playlistsRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT sp.id, sp.name
                FROM App\Entity\StationPlaylist sp
                WHERE sp.station = :station
                AND sp.source = :sourceSongs
            DQL
        )->setParameter('station', $request->getStation())
            ->setParameter('sourceSongs', PlaylistSources::Songs->value)
            ->getArrayResult();

        return $response->withJson(
            array_column($playlistsRaw, 'name', 'id')
        );
    }
}
