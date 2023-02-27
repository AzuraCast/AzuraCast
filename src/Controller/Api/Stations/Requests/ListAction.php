<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Requests;

use App\Controller\Api\Stations\AbstractSearchableListAction;
use App\Entity\Api\Error;
use App\Entity\Api\StationRequest;
use App\Entity\ApiGenerator\SongApiGenerator;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\AutoDJ\Scheduler;
use App\Service\Meilisearch;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
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
    )
]
final class ListAction extends AbstractSearchableListAction
{
    public function __construct(
        EntityManagerInterface $em,
        SongApiGenerator $songApiGenerator,
        Meilisearch $meilisearch,
        CacheItemPoolInterface $psr6Cache,
        private readonly Scheduler $scheduler
    ) {
        parent::__construct($em, $songApiGenerator, $meilisearch, $psr6Cache);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        // Verify that the station supports on-demand streaming.
        if (!$station->getEnableRequests()) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('This station does not support requests.')));
        }

        $paginator = $this->getPaginator(
            $request,
            $this->getPlaylists($station)
        );

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function (StationMedia $media) use ($station, $router) {
                $row = new StationRequest();
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

    /**
     * @param Station $station
     * @return int[]
     */
    private function getPlaylists(
        Station $station
    ): array {
        $item = $this->psr6Cache->getItem('station_' . $station->getIdRequired() . '_requestable_playlists');

        if (!$item->isHit()) {
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

            /** @var StationPlaylist $playlist */
            foreach ($playlists as $playlist) {
                if ($this->scheduler->isPlaylistScheduledToPlayNow($playlist, $now)) {
                    $ids[] = $playlist->getIdRequired();
                }
            }

            $item->set($ids);
            $item->expiresAfter(600);

            $this->psr6Cache->saveDeferred($item);
        }

        return $item->get();
    }
}
