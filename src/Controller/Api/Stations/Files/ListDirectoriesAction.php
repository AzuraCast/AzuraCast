<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class ListDirectoriesAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem
    ): ResponseInterface {
        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        $file_path = $request->getAttribute('file_path');

        if (!empty($request->getAttribute('file'))) {
            $file_meta = $fs->getMetadata($file_path);

            if ('dir' !== $file_meta['type']) {
                return $response->withStatus(500)
                    ->withJson(new Entity\Api\Error(500, __('Path "%s" is not a folder.', $file_path)));
            }
        }

        $directories = array_filter(array_map(function ($file) {
            if ('dir' !== $file['type']) {
                return null;
            }

            return [
                'name' => $file['basename'],
                'path' => $file['path'],
            ];
        }, $fs->listContents($file_path)));

        return $response->withJson([
            'rows' => array_values($directories),
        ]);
    }
}