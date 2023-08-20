<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Controller\SingleActionInterface;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

final class ListDirectoriesAction implements SingleActionInterface
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

        $currentDir = $request->getParam('currentDirectory', '');

        $fsMedia = $this->stationFilesystems->getMediaFilesystem($station);

        $directoriesRaw = $fsMedia->listContents($currentDir, false)->filter(
            fn(StorageAttributes $attrs) => $attrs->isDir()
                && !StationFilesystems::isDotFile($attrs->path())
        )->sortByPath();

        $directories = [];
        foreach ($directoriesRaw as $directory) {
            /** @var StorageAttributes $directory */
            $path = $directory->path();

            $directories[] = [
                'name' => basename($path),
                'path' => $path,
            ];
        }

        return $response->withJson(
            [
                'rows' => $directories,
            ]
        );
    }
}
