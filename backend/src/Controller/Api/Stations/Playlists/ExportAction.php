<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/playlist/{id}/export/{format}',
    operationId: 'getExportPlaylist',
    summary: 'Export a playlist contents in standard media player playlist format.',
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
        new OA\Parameter(
            name: 'format',
            description: 'Export Playlist Format',
            in: 'path',
            required: true,
            schema: new OA\Schema(
                type: 'string',
                default: 'pls',
                enum: ['pls', 'm3u']
            )
        ),
    ],
    responses: [
        new OpenApi\Response\SuccessWithDownload(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class ExportAction implements SingleActionInterface
{
    public function __construct(
        private StationPlaylistRepository $playlistRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $id */
        $id = $params['id'];

        /** @var string $format */
        $format = $params['format'] ?? 'pls';

        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        $exportFileName = 'playlist_' . StationPlaylist::generateShortName($record->name) . '.' . $format;
        $exportLines = [];

        switch (strtolower($format)) {
            case 'm3u':
                $contentType = 'application/x-mpegURL';
                foreach ($record->media_items as $mediaItem) {
                    $exportLines[] = $mediaItem->media->path;
                }
                break;

            case 'pls':
                $contentType = 'audio/x-scpls';
                $exportLines[] = '[playlist]';

                $i = 0;
                foreach ($record->media_items as $mediaItem) {
                    $i++;

                    $media = $mediaItem->media;

                    $exportLines[] = 'File' . $i . '=' . $media->path;
                    $exportLines[] = 'Title' . $i . '=' . $media->artist . ' - ' . $media->title;
                    $exportLines[] = 'Length' . $i . '=' . $media->length;
                    $exportLines[] = '';
                }

                $exportLines[] = 'NumberOfEntries=' . $i;
                $exportLines[] = 'Version=2';
                break;

            default:
                throw new InvalidArgumentException('Invalid format specified.');
        }

        $response->getBody()->write(implode("\n", $exportLines));

        return $response->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', 'attachment; filename=' . $exportFileName);
    }
}
