<?php
namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use Psr\Http\Message\ResponseInterface;

class DeleteArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem,
        Entity\Repository\StationMediaRepository $mediaRepo,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();
        $fs = $filesystem->getForStation($station);

        $media = $mediaRepo->find($media_id, $station);
        if (!($media instanceof Entity\StationMedia)) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found.')));
        }

        $mediaRepo->removeAlbumArt($media);

        return $response->withJson(new Entity\Api\Status());
    }
}
