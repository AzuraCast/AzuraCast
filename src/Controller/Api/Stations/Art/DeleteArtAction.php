<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DeleteArtAction
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param Entity\Repository\StationMediaRepository $mediaRepo
     * @param int|string $media_id
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMediaRepository $mediaRepo,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $media = $mediaRepo->find($media_id, $station);
        if (!($media instanceof Entity\StationMedia)) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $mediaRepo->removeAlbumArt($media);

        return $response->withJson(new Entity\Api\Status());
    }
}
