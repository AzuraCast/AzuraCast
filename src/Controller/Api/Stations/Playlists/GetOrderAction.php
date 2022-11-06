<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetOrderAction
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly ReloadableEntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $station = $request->getStation();
        $record = $this->playlistRepo->requireForStation($id, $station);

        if (
            PlaylistSources::Songs !== $record->getSourceEnum()
            || PlaylistOrders::Sequential !== $record->getOrderEnum()
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
                static function (array $row) use ($router, $station): array {
                    $row['media']['links'] = [
                        'play' => $router->named(
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
