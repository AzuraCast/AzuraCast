<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @extends AbstractScheduledEntityController<Entity\StationPlaylist>
 */
class PlaylistsController extends AbstractScheduledEntityController
{
    protected string $entityClass = Entity\StationPlaylist::class;
    protected string $resourceRouteName = 'api:stations:playlist';

    /**
     * @OA\Get(path="/station/{station_id}/playlists",
     *   tags={"Stations: Playlists"},
     *   description="List all current playlists.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StationPlaylist"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/playlists",
     *   tags={"Stations: Playlists"},
     *   description="Create a new playlist.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationPlaylist")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationPlaylist")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/playlist/{id}",
     *   tags={"Stations: Playlists"},
     *   description="Retrieve details for a single playlist.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Playlist ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationPlaylist")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/playlist/{id}",
     *   tags={"Stations: Playlists"},
     *   description="Update details of a single playlist.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationPlaylist")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Playlist ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/station/{station_id}/playlist/{id}",
     *   tags={"Stations: Playlists"},
     *   description="Delete a single playlist relay.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Playlist ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    /**
     * @inheritDoc
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $qb = $this->em->createQueryBuilder()
            ->select('sp, spc')
            ->from(Entity\StationPlaylist::class, 'sp')
            ->leftJoin('sp.schedule_items', 'spc')
            ->where('sp.station = :station')
            ->orderBy('sp.name', 'ASC')
            ->setParameter('station', $station);

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
    public function scheduleAction(ServerRequest $request, Response $response): ResponseInterface
    {
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
                    'edit_url' => (string)$request->getRouter()->named(
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
            'toggle' => (string)$router->fromHere(
                'api:stations:playlist:toggle',
                ['id' => $record->getId()],
                [],
                !$isInternal
            ),
            'order' => (string)$router->fromHere(
                'api:stations:playlist:order',
                ['id' => $record->getId()],
                [],
                !$isInternal
            ),
            'reshuffle' => (string)$router->fromHere(
                route_name: 'api:stations:playlist:reshuffle',
                route_params: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'queue' => (string)$router->fromHere(
                route_name: 'api:stations:playlist:queue',
                route_params: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'import' => (string)$router->fromHere(
                route_name: 'api:stations:playlist:import',
                route_params: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'clone' => (string)$router->fromHere(
                route_name: 'api:stations:playlist:clone',
                route_params: ['id' => $record->getId()],
                absolute: !$isInternal
            ),
            'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], !$isInternal),
        ];

        foreach (['pls', 'm3u'] as $format) {
            $return['links']['export'][$format] = (string)$router->fromHere(
                route_name: 'api:stations:playlist:export',
                route_params: ['id' => $record->getId(), 'format' => $format],
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
