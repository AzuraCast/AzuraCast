<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DownloadAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id,
        Filesystem $filesystem,
        Entity\Repository\StationMediaRepository $mediaRepo
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();

        $media = $mediaRepo->find($id, $station);

        if (!$media instanceof Entity\StationMedia) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, 'Not Found'));
        }

        $fs = $filesystem->getForStation($station);

        return $response->withFlysystemFile($fs, $media->getPathUri());
    }
}