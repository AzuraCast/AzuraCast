<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\PlaylistConfiguration\PlaylistConfigurationExporter;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[
    OA\Get(
        path: '/station/{station_id}/playlists/export-config',
        operationId: 'getStationPlaylistsExportConfig',
        summary: "Export all of a station's playlists as a JSON configuration dump.",
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\SuccessWithDownload(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/playlist/{id}/export-config',
        operationId: 'getStationPlaylistExportConfig',
        summary: 'Export a single playlist (and any group members) as a JSON configuration dump.',
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
            new OpenApi\Response\SuccessWithDownload(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class ExportConfigAction implements SingleActionInterface
{
    public function __construct(
        private StationPlaylistRepository $playlistRepo,
        private PlaylistConfigurationExporter $exporter
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (isset($params['id'])) {
            $record = $this->playlistRepo->requireForStation($params['id'], $station);
            $dump = $this->exporter->exportPlaylist($record);
            $fileName = 'playlist_' . StationPlaylist::generateShortName($record->name) . '.json';
        } else {
            $dump = $this->exporter->exportStationPlaylists($station);
            $fileName = 'station_' . $station->id . '_playlists.json';
        }

        $json = json_encode(
            $dump,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        return $response->renderStringAsFile($json, 'application/json', $fileName);
    }
}
