<?php

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class PostArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMediaRepository $mediaRepo,
        EntityManagerInterface $em,
        $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        $media = $mediaRepo->find($media_id, $station);
        if (!($media instanceof Entity\StationMedia)) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found.')));
        }

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $mediaRepo->updateAlbumArt(
            $media,
            $flowResponse->readAndDeleteUploadedFile()
        );
        $em->flush();

        return $response->withJson(new Entity\Api\Status());
    }
}
