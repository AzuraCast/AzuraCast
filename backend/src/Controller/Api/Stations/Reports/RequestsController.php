<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Api\Status;
use App\Entity\Repository\StationRequestRepository;
use App\Entity\StationRequest;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Utilities\Types;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\AbstractQuery;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;

#[
    OA\Get(
        path: '/station/{station_id}/reports/requests',
        operationId: 'getStationRequestsReport',
        summary: 'List station requests.',
        tags: [OpenApi::TAG_STATIONS_REPORTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO API Response
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/reports/requests/clear',
        operationId: 'postStationRequestsClear',
        summary: 'Clear all unplayed station requests.',
        tags: [OpenApi::TAG_STATIONS_REPORTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/reports/requests/{request_id}',
        operationId: 'deleteStationRequest',
        summary: 'Delete an individual station request.',
        tags: [OpenApi::TAG_STATIONS_REPORTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'request_id',
                description: 'Request ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class RequestsController
{
    use EntityManagerAwareTrait;
    use CanSortResults;
    use CanSearchResults;

    public function __construct(
        private readonly Serializer $serializer,
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

        $type = Types::string($request->getParam('type', 'recent'));
        $qb = match ($type) {
            'history' => $qb->andWhere('sr.played_at IS NOT NULL'),
            default => $qb->andWhere('sr.played_at IS NULL'),
        };

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'name' => 'sm.title',
                'title' => 'sm.title',
                'artist' => 'sm.artist',
                'album' => 'sm.album',
                'genre' => 'sm.genre',
            ],
            'sr.timestamp',
            Order::Descending
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'sm.title',
                'sm.artist',
                'sm.album',
            ]
        );

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

            return $this->serializer->normalize($row);
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
