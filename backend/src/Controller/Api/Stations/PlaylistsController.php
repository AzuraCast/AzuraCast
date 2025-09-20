<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\DateRange;
use Doctrine\ORM\AbstractQuery;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/** @extends AbstractScheduledEntityController<StationPlaylist> */
#[
    OA\Get(
        path: '/station/{station_id}/playlists',
        operationId: 'getPlaylists',
        summary: 'List all current playlists.',
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationPlaylist::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/playlists',
        operationId: 'addPlaylist',
        summary: 'Create a new playlist.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationPlaylist::class)
        ),
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationPlaylist::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/playlist/{id}',
        operationId: 'getPlaylist',
        summary: 'Retrieve details for a single playlist.',
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Playlist ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: StationPlaylist::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/playlist/{id}',
        operationId: 'editPlaylist',
        summary: 'Update details of a single playlist.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: StationPlaylist::class)
        ),
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Playlist ID',
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
    ),
    OA\Delete(
        path: '/station/{station_id}/playlist/{id}',
        operationId: 'deletePlaylist',
        summary: 'Delete a single playlist.',
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'id',
                description: 'Playlist ID',
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
final class PlaylistsController extends AbstractScheduledEntityController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = StationPlaylist::class;
    protected string $resourceRouteName = 'api:stations:playlist';

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('sp, spc')
            ->from(StationPlaylist::class, 'sp')
            ->leftJoin('sp.schedule_items', 'spc')
            ->where('sp.station = :station')
            ->setParameter('station', $station);

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'name' => 'sp.name',
            ],
            'sp.name'
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'sp.name',
                'sp.description',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    /**
     * Controller used to respond to AJAX requests from the playlist "Schedule View".
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function scheduleAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $scheduleItems = $this->em->createQuery(
            <<<'DQL'
                SELECT ssc, sp
                FROM App\Entity\StationSchedule ssc
                JOIN ssc.playlist sp
                WHERE sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1
            DQL
        )->setParameter('station', $station)
            ->execute();

        return $this->renderEvents(
            $request,
            $response,
            $scheduleItems,
            function (
                Station $station,
                StationSchedule $scheduleItem,
                DateRange $dateRange
            ) use (
                $request
            ) {
                /** @var StationPlaylist $playlist */
                $playlist = $scheduleItem->playlist;

                return [
                    'id' => $playlist->id,
                    'title' => $playlist->name,
                    'start' => $dateRange->start->toIso8601String(),
                    'end' => $dateRange->end->toIso8601String(),
                    'edit_url' => $request->getRouter()->named(
                        'api:stations:playlist',
                        ['station_id' => $station->id, 'id' => $playlist->id]
                    ),
                ];
            }
        );
    }

    /**
     * @return mixed[]
     */
    protected function viewRecord(object $record, ServerRequest $request): array
    {
        /** @var StationPlaylist $record */

        $return = $this->toArray($record);

        /** @var array{num_songs: int, total_length: string} $songTotals */
        $songTotals = $this->em->createQuery(
            <<<'DQL'
                SELECT count(sm.id) AS num_songs, sum(sm.length) AS total_length
                FROM App\Entity\StationMedia sm
                JOIN sm.playlists spm
                WHERE spm.playlist = :playlist
            DQL
        )->setParameter('playlist', $record)
            ->getSingleResult(AbstractQuery::HYDRATE_SCALAR);

        $return['short_name'] = StationPlaylist::generateShortName($return['name']);

        $return['num_songs'] = $songTotals['num_songs'];
        $return['total_length'] = round((float)$songTotals['total_length']);

        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $return['links'] = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'toggle' => $router->fromHere(
                routeName: 'api:stations:playlist:toggle',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'clone' => $router->fromHere(
                routeName: 'api:stations:playlist:clone',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
        ];

        if (PlaylistSources::Songs === $record->source) {
            if (PlaylistOrders::Sequential === $record->order) {
                $return['links']['order'] = $router->fromHere(
                    routeName: 'api:stations:playlist:order',
                    routeParams: ['id' => $record->id],
                    absolute: !$isInternal
                );
            }

            if (PlaylistOrders::Random !== $record->order) {
                $return['links']['queue'] = $router->fromHere(
                    routeName: 'api:stations:playlist:queue',
                    routeParams: ['id' => $record->id],
                    absolute: !$isInternal
                );
            }

            $return['links']['import'] = $router->fromHere(
                routeName: 'api:stations:playlist:import',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            );

            $return['links']['reshuffle'] = $router->fromHere(
                routeName: 'api:stations:playlist:reshuffle',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            );

            $return['links']['applyto'] = $router->fromHere(
                routeName: 'api:stations:playlist:applyto',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            );

            $return['links']['empty'] = $router->fromHere(
                routeName: 'api:stations:playlist:empty',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            );
        }

        foreach (['pls', 'm3u'] as $format) {
            $return['links']['export'][$format] = $router->fromHere(
                routeName: 'api:stations:playlist:export',
                routeParams: ['id' => $record->id, 'format' => $format],
                absolute: !$isInternal
            );
        }

        return $return;
    }

    /**
     * @return mixed[]
     */
    protected function toArray(object $record, array $context = []): array
    {
        return parent::toArray(
            $record,
            array_merge(
                $context,
                [
                    AbstractNormalizer::IGNORED_ATTRIBUTES => ['queue'],
                ]
            )
        );
    }
}
