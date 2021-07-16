<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

class ListDirectoriesAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $currentDir = $request->getParam('currentDirectory', '');

        $fsMedia = (new StationFilesystems($station))->getMediaFilesystem();

        $protectedPaths = [Entity\StationMedia::DIR_ALBUM_ART, Entity\StationMedia::DIR_WAVEFORMS];

        $directoriesRaw = $fsMedia->listContents($currentDir, false)->filter(
            function (StorageAttributes $attrs) use ($protectedPaths) {
                if (!$attrs->isDir()) {
                    return false;
                }

                if (in_array($attrs->path(), $protectedPaths, true)) {
                    return false;
                }

                return true;
            }
        );

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
