<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Api\Status;
use App\Entity\Repository\StationRequestRepository;
use App\Entity\StationRequest;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use Doctrine\ORM\AbstractQuery;
use Psr\Http\Message\ResponseInterface;

final class RequestsController
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationRequestRepository $requestRepo
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('sr, sm')
            ->from(StationRequest::class, 'sr')
            ->join('sr.track', 'sm')
            ->where('sr.station = :station')
            ->setParameter('station', $station);

        $qb = match ($request->getParam('type', 'recent')) {
            'history' => $qb->andWhere('sr.played_at != 0'),
            default => $qb->andWhere('sr.played_at = 0'),
        };

        $queryParams = $request->getQueryParams();
        $searchPhrase = trim($queryParams['searchPhrase'] ?? '');

        $sortField = (string)($queryParams['sort'] ?? '');
        $sortDirection = strtolower($queryParams['sortOrder'] ?? 'asc');

        if (!empty($sortField)) {
            match ($sortField) {
                'name', 'title' => $qb->addOrderBy('sm.title', $sortDirection),
                'artist' => $qb->addOrderBy('sm.artist', $sortDirection),
                'album' => $qb->addOrderBy('sm.album', $sortDirection),
                'genre' => $qb->addOrderBy('sm.genre', $sortDirection),
                default => null,
            };
        } else {
            $qb->addOrderBy('sr.timestamp', 'DESC');
        }

        if (!empty($searchPhrase)) {
            $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.album LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

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
        array $params
    ): ResponseInterface {
        /** @var string $requestId */
        $requestId = $params['request_id'];

        $station = $request->getStation();
        $media = $this->requestRepo->getPendingRequest($requestId, $station);

        if ($media instanceof StationRequest) {
            $this->em->remove($media);
            $this->em->flush();
        }

        return $response->withJson(Status::deleted());
    }

    public function clearAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $this->requestRepo->clearPendingRequests($station);

        return $response->withJson(Status::deleted());
    }
}
