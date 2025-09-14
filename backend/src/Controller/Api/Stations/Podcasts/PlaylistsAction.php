<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Form\SimpleFormOptions;
use App\Entity\Enums\PlaylistSources;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/podcasts/playlists',
    operationId: 'getStationPodcastPlaylists',
    summary: 'Get a list of playlists that can be associated with a podcast.',
    tags: [OpenApi::TAG_STATIONS_PODCASTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: SimpleFormOptions::class
            )
        ),
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
            SimpleFormOptions::fromArray(
                array_column($playlistsRaw, 'name', 'id')
            )
        );
    }
}
