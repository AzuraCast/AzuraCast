<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Controller\SingleActionInterface;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

final class GetApplyToAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        // Iterate all directories to show them as selectable.
        $fsIterator = $fsMedia->listContents('/', true)->filter(
            fn(StorageAttributes $attrs) => $attrs->isDir() && !StationFilesystems::isProtectedDir($attrs->path())
        )->sortByPath();

        $directories = [
            '/' => '/ (' . __('Base Directory') . ')',
        ];

        /** @var StorageAttributes $dir */
        foreach ($fsIterator->getIterator() as $dir) {
            $directories[$dir->path()] = $dir->path();
        }

        return $response->withJson(
            [
                'directories' => $directories,
            ]
        );
    }
}
