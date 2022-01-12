<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Automation;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PutSettingsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        ReloadableEntityManagerInterface $em
    ): ResponseInterface {
        $station = $request->getStation();

        $station = $em->refetch($station);
        $station->setAutomationSettings((array)$request->getParsedBody());

        $em->persist($station);
        $em->flush();

        return $response->withJson(Entity\Api\Status::updated());
    }
}
