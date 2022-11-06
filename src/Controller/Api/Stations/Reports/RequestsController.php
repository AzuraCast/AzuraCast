<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class RequestsController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\StationRequestRepository $requestRepo
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('sr, sm')
            ->from(Entity\StationRequest::class, 'sr')
            ->join('sr.track', 'sm')
            ->where('sr.station = :station')
            ->setParameter('station', $station)
            ->orderBy('sr.timestamp', 'DESC');

        $qb = match ($request->getParam('type', 'recent')) {
            'history' => $qb->andWhere('sr.played_at != 0'),
            default => $qb->andWhere('sr.played_at = 0'),
        };

        $query = $qb->getQuery()
            ->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = Paginator::fromQuery($query, $request);

        $router = $request->getRouter();

        $paginator->setPostprocessor(function ($row) use ($router) {
            $row['links'] = [];

            if (0 === $row['played_at']) {
                $row['links']['delete'] = $router->fromHere(
                    'api:stations:reports:requests:delete',
                    ['request_id' => $row['id']]
                );
            }

            return $row;
        });

        return $paginator->write($response);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $request_id
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
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $this->requestRepo->clearPendingRequests($station);

        return $response->withJson(Entity\Api\Status::deleted());
    }
}
