<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetOrderAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->requireRecord($station, $id);

        if (
            Entity\Enums\PlaylistSources::Songs !== $record->getSourceEnum()
            || Entity\Enums\PlaylistOrders::Sequential !== $record->getOrderEnum()
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $media_items = $this->em->createQuery(
            <<<'DQL'
                SELECT spm, sm
                FROM App\Entity\StationPlaylistMedia spm
                JOIN spm.media sm
                WHERE spm.playlist_id = :playlist_id
                ORDER BY spm.weight ASC
            DQL
        )->setParameter('playlist_id', $id)
            ->getArrayResult();

        $router = $request->getRouter();

        return $response->withJson(
            array_map(
                function (array $row) use ($router, $station): array {
                    $row['media']['links'] = [
                        'play' => (string)$router->named(
                            'api:stations:files:play',
                            ['station_id' => $station->getIdRequired(), 'id' => $row['media']['unique_id']],
                            [],
                            true
                        ),
                    ];
                    return $row;
                },
                $media_items
            )
        );
    }
}
