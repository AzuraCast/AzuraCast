<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
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
final class RequestsController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\StationRequestRepository $requestRepo,
        private readonly Entity\ApiGenerator\SongApiGenerator $songApiGenerator,
        private readonly Scheduler $scheduler
    ) {
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $playlistIds = $this->getRequestablePlaylists($station);

        $qb = $this->em->createQueryBuilder();
        $qb->select('sm, spm, sp')
            ->from(Entity\StationMedia::class, 'sm')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.storage_location = :storageLocation')
            ->andWhere('sp.id IN (:playlistIds)')
            ->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->setParameter('playlistIds', $playlistIds);

        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['sort'])) {
            $sortDirection = (($queryParams['sortOrder'] ?? 'ASC') === 'ASC') ? 'ASC' : 'DESC';

            match ($queryParams['sort']) {
                'name', 'song_title' => $qb->addOrderBy('sm.title', $sortDirection),
                'song_artist' => $qb->addOrderBy('sm.artist', $sortDirection),
                'song_album' => $qb->addOrderBy('sm.album', $sortDirection),
                'song_genre' => $qb->addOrderBy('sm.genre', $sortDirection),
                default => null,
            };
        } else {
            $qb->orderBy('sm.artist', 'ASC')
                ->addOrderBy('sm.title', 'ASC');
        }

        $search_phrase = trim($queryParams['searchPhrase'] ?? '');
        if (!empty($search_phrase)) {
            $qb->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query OR sm.album LIKE :query)')
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $paginator = Paginator::fromQueryBuilder($qb, $request);

        $router = $request->getRouter();
        $baseUrl = $router->getBaseUrl();

        $paginator->setPostprocessor(
            function (Entity\StationMedia $media_row) use ($station, $baseUrl, $router) {
                $row = new Entity\Api\StationRequest();
                $row->song = ($this->songApiGenerator)($media_row, $station, $baseUrl);
                $row->request_id = $media_row->getUniqueId();
                $row->request_url = $router->named(
                    'api:requests:submit',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $media_row->getUniqueId(),
                    ]
                );

                $row->resolveUrls($baseUrl);

                return $row;
            }
        );

        return $paginator->write($response);
    }

    /**
     * @param Entity\Station $station
     */
    private function getRequestablePlaylists(Entity\Station $station): array
    {
        $playlists = $this->em->createQuery(
            <<<DQL
            SELECT sp FROM App\Entity\StationPlaylist sp
            WHERE sp.station = :station
            AND sp.is_enabled = 1 AND sp.include_in_requests = 1
            DQL
        )->setParameter('station', $station)
            ->toIterable();

        $ids = [];
        $now = CarbonImmutable::now($station->getTimezoneObject());

        /** @var Entity\StationPlaylist $playlist */
        foreach ($playlists as $playlist) {
            if ($this->scheduler->isPlaylistScheduledToPlayNow($playlist, $now)) {
                $ids[] = $playlist->getIdRequired();
            }
        }

        return $ids;
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
