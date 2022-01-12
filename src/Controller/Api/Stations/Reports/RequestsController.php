<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class RequestsController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationRequestRepository $requestRepo
    ) {
    }

    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $requests = $this->em->createQuery(
            <<<'DQL'
                SELECT sr, sm
                FROM App\Entity\StationRequest sr
                JOIN sr.track sm
                WHERE sr.station = :station
                ORDER BY sr.timestamp DESC
            DQL
        )->setParameter('station', $station)
            ->getArrayResult();

        $paginator = Paginator::fromArray($requests, $request);

        $router = $request->getRouter();
        $postProcessor = function ($row) use ($router) {
            $row['links'] = [
                'delete' => (string)$router->fromHere(
                    'api:stations:reports:requests:delete',
                    ['request_id' => $row['id']]
                ),
            ];

            return $row;
        };
        $paginator->setPostprocessor($postProcessor);

        return $paginator->write($response);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $request_id
    ): ResponseInterface {
        $station = $request->getStation();
        $media = $this->requestRepo->getPendingRequest($request_id, $station);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();
        }

        return $response->withJson(Entity\Api\Status::deleted());
    }

    public function clearAction(
        ServerRequest $request,
        Response $response,
    ): ResponseInterface {
        $station = $request->getStation();
        $this->requestRepo->clearPendingRequests($station);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
