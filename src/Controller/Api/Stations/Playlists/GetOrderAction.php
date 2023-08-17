<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistRepository;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetOrderAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        $station = $request->getStation();
        $record = $this->playlistRepo->requireForStation($id, $station);

        if (
            PlaylistSources::Songs !== $record->getSource()
            || PlaylistOrders::Sequential !== $record->getOrder()
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $mediaItems = $this->em->createQuery(
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
                $mediaItems
            )
        );
    }
}
