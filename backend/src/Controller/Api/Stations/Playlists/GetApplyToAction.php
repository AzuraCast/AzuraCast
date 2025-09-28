<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationPlaylistRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use League\Flysystem\StorageAttributes;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/playlist/{id}/apply-to',
    operationId: 'getStationPlaylistApplyTo',
    summary: 'Get a list of directories that the given playlist can apply to.',
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
final readonly class GetApplyToAction implements SingleActionInterface
{
    public function __construct(
        private StationPlaylistRepository $playlistRepo,
        private StationFilesystems $stationFilesystems
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
        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        // Iterate all directories to show them as selectable.
        $fsIterator = $fsMedia->listContents('/', true)->filter(
            fn(StorageAttributes $attrs) => $attrs->isDir() && !StationFilesystems::isDotFile($attrs->path())
        )->sortByPath();

        $directories = [
            [
                'path' => "",
                'name' => '/ (' . __('Base Directory') . ')',
            ],
        ];

        /** @var StorageAttributes $dir */
        foreach ($fsIterator->getIterator() as $dir) {
            $directories[] = [
                'path' => $dir->path(),
                'name' => '/' . $dir->path(),
            ];
        }

        return $response->withJson(
            [
                'playlist' => [
                    'id' => $record->id,
                    'name' => $record->name,
                ],
                'directories' => $directories,
            ]
        );
    }
}
