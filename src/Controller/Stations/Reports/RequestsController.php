<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class RequestsController
{
    protected string $csrf_namespace = 'stations_requests';

    public function __construct(
        protected EntityManagerInterface $em
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $requests = $this->em->createQuery(
            <<<'DQL'
                SELECT sr, sm
                FROM App\Entity\StationRequest sr
                JOIN sr.track sm
                WHERE sr.station_id = :station_id
                ORDER BY sr.timestamp DESC
            DQL
        )->setParameter('station_id', $station->getId())
            ->getArrayResult();

        return $request->getView()->renderToResponse($response, 'stations/reports/requests', [
            'requests' => $requests,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $request_id,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $station = $request->getStation();

        $media = $this->em->getRepository(Entity\StationRequest::class)->findOneBy([
            'id' => $request_id,
            'station_id' => $station->getId(),
            'played_at' => 0,
        ]);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();

            $request->getFlash()->addMessage('<b>Request deleted!</b>', Flash::SUCCESS);
        }

        return $response->withRedirect((string)$request->getRouter()->fromHere('stations:reports:requests'));
    }

    public function clearAction(
        ServerRequest $request,
        Response $response,
        string $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $station = $request->getStation();

        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationRequest sr
                WHERE sr.station = :station
                AND sr.played_at = 0
            DQL
        )->setParameter('station', $station)
            ->execute();

        $request->getFlash()->addMessage('<b>All pending requests cleared.</b>', Flash::SUCCESS);

        return $response->withRedirect((string)$request->getRouter()->fromHere('stations:reports:requests'));
    }
}
