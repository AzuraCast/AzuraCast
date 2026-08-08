<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Api\StationPlaylistComputedFields;
use App\Entity\Api\StationScheduleGroupMember;
use App\Entity\Api\StationSchedulePlaylistEvent;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\DateRange;
use Doctrine\ORM\AbstractQuery;
use InvalidArgumentException;
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
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: StationPlaylist::class),
                            new OA\Schema(ref: HasLinks::class),
                            new OA\Schema(ref: StationPlaylistComputedFields::class),
                        ]
                    )
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
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: StationPlaylist::class),
                        new OA\Schema(ref: HasLinks::class),
                        new OA\Schema(ref: StationPlaylistComputedFields::class),
                    ]
                )
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
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: StationPlaylist::class),
                        new OA\Schema(ref: HasLinks::class),
                        new OA\Schema(ref: StationPlaylistComputedFields::class),
                    ]
                )
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
     */
    #[OA\Get(
        path: '/station/{station_id}/playlists/schedule',
        operationId: 'getStationPlaylistsSchedule',
        summary: 'Return calendar events for the station\'s playlist schedule.',
        tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: StationSchedulePlaylistEvent::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )]
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

        $eventCache = [];

        return $this->renderEvents(
            $request,
            $response,
            $scheduleItems,
            function (
                Station $station,
                StationSchedule $scheduleItem,
                DateRange $dateRange
            ) use (
                $request,
                &$eventCache
            ): StationSchedulePlaylistEvent {
                /** @var StationPlaylist $playlist */
                $playlist = $scheduleItem->playlist;

                $event = clone ($eventCache[$playlist->id] ??= $this->buildPlaylistScheduleEvent(
                    $station,
                    $playlist,
                    $request
                ));

                $event->start = $dateRange->start->toIso8601String();
                $event->end = $dateRange->end->toIso8601String();
                $event->has_group_schedule_conflict = $this->scheduler->isPlaylistBlockedByGroupSchedule(
                    $playlist,
                    $dateRange
                );

                return $event;
            }
        );
    }

    private function buildPlaylistScheduleEvent(
        Station $station,
        StationPlaylist $playlist,
        ServerRequest $request
    ): StationSchedulePlaylistEvent {
        $event = new StationSchedulePlaylistEvent();
        $event->id = $playlist->id;
        $event->title = $playlist->name;
        $event->edit_url = $request->getRouter()->named(
            'api:stations:playlist',
            [
                'station_id' => $station->id,
                'id' => $playlist->id,
            ]
        );
        $event->source = $playlist->source;
        $event->order = $playlist->order;
        $event->playlist_type = $playlist->type;
        $event->play_per_songs = $playlist->play_per_songs;
        $event->play_per_minutes = $playlist->play_per_minutes;
        $event->play_per_hour_minute = $playlist->play_per_hour_minute;
        $event->weight = $playlist->weight;
        $event->is_jingle = $playlist->is_jingle;
        $event->include_in_on_demand = $playlist->include_in_on_demand;
        $event->avoid_duplicates = $playlist->avoid_duplicates;

        switch ($playlist->source) {
            case PlaylistSources::Songs:
                /** @var array{num_songs: int, total_length: ?string} $totals */
                $totals = $this->em->createQuery(
                    <<<'DQL'
                        SELECT count(sm.id) AS num_songs, sum(sm.length) AS total_length
                        FROM App\Entity\StationMedia sm
                        JOIN sm.playlists spm
                        WHERE spm.playlist = :playlist
                    DQL
                )->setParameter('playlist', $playlist)
                    ->getSingleResult(AbstractQuery::HYDRATE_SCALAR);

                $event->num_songs = (int) $totals['num_songs'];
                $event->total_length = round((float) $totals['total_length']);
                break;

            case PlaylistSources::Playlists:
                $members = [];
                foreach ($playlist->playlists as $groupMember) {
                    $memberPlaylist = $groupMember->playlist;

                    $member = new StationScheduleGroupMember();
                    $member->id = $memberPlaylist->id;
                    $member->name = $memberPlaylist->name;
                    $member->source = $memberPlaylist->source;
                    $member->order = $memberPlaylist->order;
                    $member->weight = $groupMember->weight;
                    $member->count = match ($memberPlaylist->source) {
                        PlaylistSources::Songs => $memberPlaylist->media_items->count(),
                        PlaylistSources::Playlists => $memberPlaylist->playlists->count(),
                        default => null,
                    };
                    $member->consecutive_plays = $groupMember->consecutive_plays;
                    $member->play_full_cycle = $groupMember->play_full_cycle;
                    $member->is_enabled = $memberPlaylist->is_enabled;

                    $members[] = $member;
                }
                $event->members = $members;
                break;

            case PlaylistSources::RemoteUrl:
                $event->remote_url = $playlist->remote_url;
                $event->remote_type = $playlist->remote_type;
                break;

            default:
                break;
        }

        return $event;
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

        $parentGroups = [];
        foreach ($record->playlist_groups as $spg) {
            $group = $spg->playlist_group;
            $parentGroups[$group->id] = [
                'id' => $group->id,
                'name' => $group->name,
            ];
        }
        $return['playlist_groups'] = array_values($parentGroups);

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
            'export_config' => $router->fromHere(
                routeName: 'api:stations:playlist:export_config',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
        ];

        if (in_array($record->source, [PlaylistSources::Songs, PlaylistSources::Playlists])) {
            if (PlaylistOrders::Sequential === $record->order) {
                $return['links']['order'] = $router->fromHere(
                    routeName: 'api:stations:playlist:order',
                    routeParams: ['id' => $record->id],
                    absolute: !$isInternal
                );
            }

            $return['links']['reshuffle'] = $router->fromHere(
                routeName: 'api:stations:playlist:reshuffle',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            );
        }

        if (PlaylistSources::Playlists === $record->source) {
            $return['links']['members'] = $router->fromHere(
                routeName: 'api:stations:playlist:members',
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            );
        }

        if (PlaylistSources::Songs === $record->source) {
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

    protected function editRecord(?array $data, ?object $record = null, array $context = []): object
    {
        if (null === $data) {
            throw new InvalidArgumentException('Could not parse input data.');
        }

        $source = PlaylistSources::tryFrom($data['source'] ?? '');
        if ($source === PlaylistSources::Playlists || $source === PlaylistSources::Requests) {
            $data['include_in_on_demand'] = false;
            $data['include_in_requests'] = false;
            $data['is_jingle'] = false;
            $data['backend_options'] = [];

            $type = PlaylistTypes::tryFrom($data['type'] ?? '');
            $data['type'] = ($type === PlaylistTypes::Advanced)
                ? PlaylistTypes::Standard->value
                : $data['type'];
        }

        return parent::editRecord($data, $record, $context);
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
