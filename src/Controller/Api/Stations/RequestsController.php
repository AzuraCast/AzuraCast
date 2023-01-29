<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Service\Meilisearch;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/requests',
        operationId: 'getRequestableSongs',
        description: 'Return a list of requestable songs.',
        tags: ['Stations: Song Requests'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_StationRequest')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/request/{request_id}',
        operationId: 'submitSongRequest',
        description: 'Submit a song request.',
        tags: ['Stations: Song Requests'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'request_id',
                description: 'The requestable song ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final readonly class RequestsController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Entity\Repository\StationRequestRepository $requestRepo,
        private Entity\ApiGenerator\SongApiGenerator $songApiGenerator,
        private Meilisearch $meilisearch
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableRequests()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This station does not support requests.')));
        }

        if (!$this->meilisearch->isSupported()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('This feature is not supported on this installation.')));
        }

        $index = $this->meilisearch->getIndex($station->getMediaStorageLocation());

        $queryParams = $request->getQueryParams();
        $searchPhrase = trim($queryParams['searchPhrase'] ?? '');

        $searchParams = [];
        if (!empty($queryParams['sort'])) {
            $sortField = (string)$queryParams['sort'];
            $sortDirection = strtolower($queryParams['sortOrder'] ?? 'asc');
            $searchParams['sort'] = [$sortField . ':' . $sortDirection];
        }

        $hydrateCallback = function (array $results) {
            $ids = array_column($results, 'id');

            return $this->em->createQuery(
                <<<'DQL'
                    SELECT sm
                    FROM App\Entity\StationMedia sm
                    WHERE sm.id IN (:ids)
                    ORDER BY FIELD(sm.id, :ids)
                DQL
            )->setParameter('ids', $ids)
                ->toIterable();
        };

        $paginatorAdapter = $index->getOnDemandSearchPaginator(
            $station,
            $hydrateCallback,
            $searchPhrase,
            $searchParams,
        );

        $paginator = Paginator::fromAdapter($paginatorAdapter, $request);

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function (Entity\StationMedia $media) use ($station, $router) {
                $row = new Entity\Api\StationRequest();
                $row->song = ($this->songApiGenerator)($media, $station, $router->getBaseUrl());
                $row->request_id = $media->getUniqueId();
                $row->request_url = $router->named(
                    'api:requests:submit',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $media->getUniqueId(),
                    ]
                );

                $row->resolveUrls($router->getBaseUrl());

                return $row;
            }
        );

        return $paginator->write($response);
    }

    public function submitAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        try {
            $user = $request->getUser();
        } catch (Exception\InvalidRequestAttribute) {
            $user = null;
        }

        $isAuthenticated = ($user instanceof Entity\User);

        try {
            $this->requestRepo->submit(
                $station,
                $media_id,
                $isAuthenticated,
                $request->getIp(),
                $request->getHeaderLine('User-Agent')
            );

            return $response->withJson(Entity\Api\Status::success());
        } catch (Exception $e) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, $e->getMessage()));
        }
    }
}
