<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DeleteIntroAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMountRepository $mountRepo,
        int $id
    ): ResponseInterface {
        $station = $request->getStation();
        $mount = $mountRepo->find($station, $id);

        if (null === $mount) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $mountRepo->clearIntro($mount);

        return $response->withJson(new Entity\Api\Status());
    }
}
