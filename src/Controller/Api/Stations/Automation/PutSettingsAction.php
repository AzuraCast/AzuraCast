<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Automation;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PutSettingsAction
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $station = $this->em->refetch($station);
        $station->setAutomationSettings((array)$request->getParsedBody());

        $this->em->persist($station);
        $this->em->flush();

        return $response->withJson(Entity\Api\Status::updated());
    }
}
