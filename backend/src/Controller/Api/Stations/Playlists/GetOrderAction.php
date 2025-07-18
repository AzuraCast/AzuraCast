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
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/playlist/{id}/order',
    operationId: 'getStationPlaylistOrder',
    summary: 'Get the current order of sequential tracks in the specified playlist.',
    tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Playlist ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
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
            PlaylistSources::Songs !== $record->source
            || PlaylistOrders::Sequential !== $record->order
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $mediaItems = $this->em->createQuery(
            <<<'DQL'
                SELECT spm, sm
                FROM App\Entity\StationPlaylistMedia spm
                JOIN spm.media sm
                WHERE IDENTITY(spm.playlist) = :playlist_id
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
                            ['station_id' => $station->id, 'id' => $row['media']['unique_id']],
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
