<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\OnDemand;

use App\Controller\Api\Stations\AbstractSearchableListAction;
use App\Entity\Api\StationOnDemand;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/ondemand',
    operationId: 'getStationOnDemand',
    summary: 'List all tracks available on-demand for this station.',
    security: [],
    tags: [OpenApi::TAG_PUBLIC_STATIONS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: StationOnDemand::class
                )
            )
        ),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ListAction extends AbstractSearchableListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $playlists = $this->getPlaylists($station);
        if (empty($playlists)) {
            throw StationUnsupportedException::onDemand();
        }

        $paginator = $this->getPaginator($request, $playlists);

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function (StationMedia $media) use ($station, $router) {
                $row = new StationOnDemand();

                $row->track_id = $media->unique_id;
                $row->media = ($this->songApiGenerator)(
                    song: $media,
                    station: $station
                );

                $row->download_url = $router->named(
                    'api:stations:ondemand:download',
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
        $item = $this->psr6Cache->getItem(
            urlencode(
                'station_' . $station->id . '_on_demand_playlists'
            )
        );

        if (!$item->isHit()) {
            $playlistIds = $this->em->createQuery(
                <<<'DQL'
                SELECT sp.id FROM App\Entity\StationPlaylist sp
                WHERE sp.station = :station
                AND sp.is_enabled = 1 AND sp.include_in_on_demand = 1
                DQL
            )->setParameter('station', $station)
                ->getSingleColumnResult();

            $item->set($playlistIds);
            $item->expiresAfter(600);

            $this->psr6Cache->save($item);
        }

        return $item->get();
    }
}
