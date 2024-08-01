<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistSources;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

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
