<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class MakeDirectoryAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem
    ): ResponseInterface {
        $params = $request->getParams();

        $file_path = $request->getAttribute('file_path');

        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        $new_dir = $file_path . '/' . $params['name'];
        $dir_created = $fs->createDir($new_dir);
        if (!$dir_created) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('Directory "%s" was not created', $new_dir)));
        }

        return $response->withJson(new Entity\Api\Status());
    }
}