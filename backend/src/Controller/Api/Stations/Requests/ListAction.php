<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Requests;

use App\Controller\Api\Stations\AbstractSearchableListAction;
use App\Entity\Api\StationRequest;
use App\Entity\ApiGenerator\SongApiGenerator;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/requests',
        operationId: 'getRequestableSongs',
        summary: 'Return a list of requestable songs.',
        security: [],
        tags: [OpenApi::TAG_PUBLIC_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationRequest::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ListAction extends AbstractSearchableListAction
{
    public function __construct(
        private readonly Scheduler $scheduler,
        SongApiGenerator $songApiGenerator,
        CacheItemPoolInterface $psr6Cache,
    ) {
        parent::__construct($songApiGenerator, $psr6Cache);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $playlists = $this->getPlaylists($station);
        if (empty($playlists)) {
            throw StationUnsupportedException::requests();
        }

        $paginator = $this->getPaginator($request, $playlists);

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function (StationMedia $media) use ($station, $router) {
                $row = new StationRequest();
                $row->song = ($this->songApiGenerator)($media, $station, $router->getBaseUrl());
                $row->request_id = $media->unique_id;
                $row->request_url = $router->named(
                    'api:requests:submit',
                    [
                        'station_id' => $station->id,
                        'media_id' => $media->unique_id,
                    ]
                );

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
        $item = $this->psr6Cache->getItem('station_' . $station->id . '_requestable_playlists');

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
                if ($this->scheduler->isPlaylistScheduledToPlayNow($playlist, $now, true)) {
                    $ids[] = $playlist->id;
                }
            }

            $item->set($ids);
            $item->expiresAfter(600);

            $this->psr6Cache->save($item);
        }

        return $item->get();
    }
}
