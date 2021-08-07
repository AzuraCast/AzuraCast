<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Mounts\Intro;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

class PostIntroAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationMountRepository $mountRepo,
        ?int $id = null
    ): ResponseInterface {
        $station = $request->getStation();

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        if (null !== $id) {
            $mount = $mountRepo->find($station, $id);
            if (null === $mount) {
                return $response->withStatus(404)
                    ->withJson(Entity\Api\Error::notFound());
            }

            $mountRepo->setIntro($mount, $flowResponse);

            return $response->withJson(new Entity\Api\Status());
        }

        return $response->withJson($flowResponse);
    }
}
