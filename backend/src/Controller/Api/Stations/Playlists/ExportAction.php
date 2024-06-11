<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationPlaylistRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

final class ExportAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo
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

        $exportFileName = 'playlist_' . $record->getShortName() . '.' . $format;
        $exportLines = [];

        switch (strtolower($format)) {
            case 'm3u':
                $contentType = 'application/x-mpegURL';
                foreach ($record->getMediaItems() as $mediaItem) {
                    $exportLines[] = $mediaItem->getMedia()->getPath();
                }
                break;

            case 'pls':
                $contentType = 'audio/x-scpls';
                $exportLines[] = '[playlist]';

                $i = 0;
                foreach ($record->getMediaItems() as $mediaItem) {
                    $i++;

                    $media = $mediaItem->getMedia();

                    $exportLines[] = 'File' . $i . '=' . $media->getPath();
                    $exportLines[] = 'Title' . $i . '=' . $media->getArtist() . ' - ' . $media->getTitle();
                    $exportLines[] = 'Length' . $i . '=' . $media->getLength();
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
