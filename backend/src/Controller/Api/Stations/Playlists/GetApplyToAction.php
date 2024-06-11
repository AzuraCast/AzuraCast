<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationPlaylistRepository;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

final class GetApplyToAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly StationFilesystems $stationFilesystems
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
                    'id' => $record->getIdRequired(),
                    'name' => $record->getName(),
                ],
                'directories' => $directories,
            ]
        );
    }
}
