<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetIntroAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMountRepository $mountRepo,
        int $id
    ): ResponseInterface {
        set_time_limit(600);

        $station = $request->getStation();
        $mount = $mountRepo->find($station, $id);

        if ($mount instanceof Entity\StationMount) {
            $introPath = $mount->getIntroPath();

            if (!empty($introPath)) {
                $fsConfig = (new StationFilesystems($station))->getConfigFilesystem();

                if ($fsConfig->fileExists($introPath)) {
                    return $response->streamFilesystemFile(
                        $fsConfig,
                        $introPath,
                        basename($introPath)
                    );
                }
            }
        }

        return $response->withStatus(404)
            ->withJson(Entity\Api\Error::notFound());
    }
}
