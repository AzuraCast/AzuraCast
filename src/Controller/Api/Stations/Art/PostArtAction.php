<?php

namespace App\Controller\Api\Stations\Art;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

class PostArtAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem,
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

        $files = $request->getUploadedFiles();
        if (!empty($files['art'])) {
            $file = $files['art'];

            /** @var UploadedFileInterface $file */
            if ($file->getError() === UPLOAD_ERR_OK) {
                $mediaRepo->writeAlbumArt($media, $file->getStream()->getContents());
                $em->flush();
            } elseif ($file->getError() !== UPLOAD_ERR_NO_FILE) {
                return $response->withStatus(500)
                    ->withJson(new Entity\Api\Error(500, $file->getError()));
            }
        }

        return $response->withJson(new Entity\Api\Status());
    }
}
