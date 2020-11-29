<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ListDirectoriesAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem
    ): ResponseInterface {
        $station = $request->getStation();
        $fs = $filesystem->getPrefixedAdapterForStation($station, FilesystemManager::PREFIX_MEDIA, true);

        $currentDir = $request->getParam('currentDirectory', '');
        if (!empty($currentDir)) {
            $dirMeta = $fs->getMetadata($currentDir);
            if ('dir' !== $dirMeta['type']) {
                return $response->withStatus(500)
                    ->withJson(new Entity\Api\Error(500, __('Path "%s" is not a folder.', $currentDir)));
            }
        }

        $protectedPaths = [Entity\StationMedia::DIR_ALBUM_ART, Entity\StationMedia::DIR_WAVEFORMS];

        $directories = array_filter(array_map(function ($file) use ($protectedPaths) {
            if ('dir' !== $file['type']) {
                return null;
            }

            if (in_array($file['path'], $protectedPaths, true)) {
                return null;
            }

            return [
                'name' => $file['basename'],
                'path' => $file['path'],
            ];
        }, $fs->listContents($currentDir)));

        return $response->withJson([
            'rows' => array_values($directories),
        ]);
    }
}
