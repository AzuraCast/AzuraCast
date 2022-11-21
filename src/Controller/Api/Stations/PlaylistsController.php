<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\CanSortResults;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/** @extends AbstractScheduledEntityController<Entity\StationPlaylist> */
#[
    OA\Get(
        path: '/station/{station_id}/playlists',
        operationId: 'getPlaylists',
        description: 'List all current playlists.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Playlists'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/StationPlaylist')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/station/{station_id}/playlists',
        operationId: 'addPlaylist',
        description: 'Create a new playlist.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationPlaylist')
        ),
        tags: ['Stations: Playlists'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationPlaylist')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/playlist/{id}',
        operationId: 'getPlaylist',
        description: 'Retrieve details for a single playlist.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Playlists'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/StationPlaylist')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/station/{station_id}/playlist/{id}',
        operationId: 'editPlaylist',
        description: 'Update details of a single playlist.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/StationPlaylist')
        ),
        tags: ['Stations: Playlists'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/station/{station_id}/playlist/{id}',
        operationId: 'deletePlaylist',
        description: 'Delete a single playlist relay.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Playlists'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class PlaylistsController extends AbstractScheduledEntityController
{
    use CanSortResults;

    protected string $entityClass = Entity\StationPlaylist::class;
    protected string $resourceRouteName = 'api:stations:playlist';

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('sp, spc')
            ->from(Entity\StationPlaylist::class, 'sp')
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

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('sp.name LIKE :name')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

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
        Response $response,
        string $station_id
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
                Entity\StationSchedule $scheduleItem,
                CarbonInterface $start,
                CarbonInterface $end
            ) use (
                $request,
                $station
            ) {
                /** @var Entity\StationPlaylist $playlist */
                $playlist = $scheduleItem->getPlaylist();

                return [
                    'id' => $playlist->getId(),
                    'title' => $playlist->getName(),
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    'edit_url' => $request->getRouter()->named(
                        'api:stations:playlist',
                        ['station_id' => $station->getId(), 'id' => $playlist->getId()]
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
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $song_totals = $this->em->createQuery(
            <<<'DQL'
                SELECT count(sm.id) AS num_songs, sum(sm.length) AS total_length
                FROM App\Entity\StationMedia sm
                JOIN sm.playlists spm
                WHERE spm.playlist = :playlist
            DQL
        )->setParameter('playlist', $record)
            ->getArrayResult();

        $return['num_songs'] = (int)$song_totals[0]['num_songs'];
        $return['total_length'] = (int)$song_totals[0]['total_length'];

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links'] = [
            'toggle' => $router->fromHere(
                'api:stations:playlist:toggle',
                ['id' => $record->getId()],
                [],
                !$isInternal
            ),
            'order' => $router->fromHere(
                'api:stations:playlist:order',
                ['id' => $record->getId()],
                [],
                !$isInternal
            ),
            'reshuffle' => $router->fromHere(
                routeName: 'api:stations:playlist:reshuffle',
                routeParams: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'queue' => $router->fromHere(
                routeName: 'api:stations:playlist:queue',
                routeParams: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'import' => $router->fromHere(
                routeName: 'api:stations:playlist:import',
                routeParams: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'clone' => $router->fromHere(
                routeName: 'api:stations:playlist:clone',
                routeParams: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'self' => $router->fromHere(
                $this->resourceRouteName,
                ['id' => $record->getId()],
                [],
                !$isInternal
            ),
        ];

        foreach (['pls', 'm3u'] as $format) {
            $return['links']['export'][$format] = $router->fromHere(
                routeName: 'api:stations:playlist:export',
                routeParams: ['id' => $record->getId(), 'format' => $format],
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
