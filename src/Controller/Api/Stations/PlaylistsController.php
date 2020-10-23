<?php

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\PlaylistParser;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

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

        $scheduleItems = $this->em->createQuery(/** @lang DQL */ 'SELECT
            ssc, sp
            FROM App\Entity\StationSchedule ssc
            JOIN ssc.playlist sp
            WHERE sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1
        ')->setParameter('station', $station)
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

    public function getOrderAction(
        ServerRequest $request,
        Response $response,
        $id
    ): ResponseInterface {
        $record = $this->getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        if (
            $record->getSource() !== Entity\StationPlaylist::SOURCE_SONGS
            || $record->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $media_items = $this->em->createQuery(/** @lang DQL */ 'SELECT spm, sm
            FROM App\Entity\StationPlaylistMedia spm
            JOIN spm.media sm
            WHERE spm.playlist_id = :playlist_id
            ORDER BY spm.weight ASC')
            ->setParameter('playlist_id', $id)
            ->getArrayResult();

        return $response->withJson($media_items);
    }

    public function putOrderAction(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepository,
        $id
    ): ResponseInterface {
        $record = $this->getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        if (
            $record->getSource() !== Entity\StationPlaylist::SOURCE_SONGS
            || $record->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $order = $request->getParam('order');

        $playlistMediaRepository->setMediaOrder($record, $order);
        return $response->withJson($order);
    }

    public function exportAction(
        ServerRequest $request,
        Response $response,
        $id,
        $format = 'pls'
    ): ResponseInterface {
        $record = $this->getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        $formats = [
            'pls' => 'audio/x-scpls',
            'm3u' => 'application/x-mpegURL',
        ];

        if (!isset($formats[$format])) {
            throw new NotFoundException(__('Format not found.'));
        }

        $file_name = 'playlist_' . $record->getShortName() . '.' . $format;

        $response->getBody()->write($record->export($format));
        return $response
            ->withHeader('Content-Type', $formats[$format])
            ->withHeader('Content-Disposition', 'attachment; filename=' . $file_name);
    }

    public function toggleAction(ServerRequest $request, Response $response, $id): ResponseInterface
    {
        $record = $this->getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        $new_value = !$record->getIsEnabled();

        $record->setIsEnabled($new_value);
        $this->em->persist($record);
        $this->em->flush();

        $flash_message = ($new_value)
            ? __('Playlist enabled.')
            : __('Playlist disabled.');

        return $response->withJson(new Entity\Api\Status(true, $flash_message));
    }

    public function reshuffleAction(ServerRequest $request, Response $response, $id): ResponseInterface
    {
        $record = $this->getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        $record->setQueue(null);
        $this->em->persist($record);
        $this->em->flush();

        return $response->withJson(new Entity\Api\Status(
            true,
            __('Playlist reshuffled.')
        ));
    }

    public function importAction(
        ServerRequest $request,
        Response $response,
        Entity\Repository\StationPlaylistMediaRepository $playlistMediaRepo,
        $id
    ): ResponseInterface {
        /** @var Entity\StationPlaylist $playlist */
        $playlist = $this->getRecord($request->getStation(), $id);

        $files = $request->getUploadedFiles();

        if (empty($files['playlist_file'])) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, 'No "playlist_file" provided.'));
        }

        /** @var UploadedFileInterface $file */
        $file = $files['playlist_file'];

        if (UPLOAD_ERR_OK !== $file->getError()) {
            return $response->withStatus(500)
                ->withJson(new Entity\Api\Error(500, $file->getError()));
        }

        $playlistFile = $file->getStream()->getContents();

        $paths = PlaylistParser::getSongs($playlistFile);

        $totalPaths = count($paths);
        $foundPaths = 0;

        if (!empty($paths)) {
            $station = $request->getStation();

            // Assemble list of station media to match against.
            $media_lookup = [];

            $media_info_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT sm.id, sm.path
                FROM App\Entity\StationMedia sm
                WHERE sm.station = :station')
                ->setParameter('station', $station)
                ->getArrayResult();

            foreach ($media_info_raw as $row) {
                $path_hash = md5($row['path']);
                $media_lookup[$path_hash] = $row['id'];
            }

            // Run all paths against the lookup list of hashes.
            $matches = [];

            foreach ($paths as $path_raw) {
                // De-Windows paths (if applicable)
                $path_raw = str_replace('\\', '/', $path_raw);

                // Work backwards from the basename to try to find matches.
                $path_parts = explode('/', $path_raw);
                for ($i = 1, $iMax = count($path_parts); $i <= $iMax; $i++) {
                    $path_attempt = implode('/', array_slice($path_parts, 0 - $i));
                    $path_hash = md5($path_attempt);

                    if (isset($media_lookup[$path_hash])) {
                        $matches[] = $media_lookup[$path_hash];
                    }
                }
            }

            // Assign all matched media to the playlist.
            if (!empty($matches)) {
                $matchedMediaRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT sm
                    FROM App\Entity\StationMedia sm
                    WHERE sm.station = :station AND sm.id IN (:matched_ids)')
                    ->setParameter('station', $station)
                    ->setParameter('matched_ids', $matches)
                    ->execute();

                /** @var Entity\StationMedia[] $mediaById */
                $mediaById = [];
                foreach ($matchedMediaRaw as $row) {
                    /** @var Entity\StationMedia $row */
                    $mediaById[$row->getId()] = $row;
                }

                $weight = $playlistMediaRepo->getHighestSongWeight($playlist);

                // Split this process to preserve the order of the imported items.
                foreach ($matches as $mediaId) {
                    $weight++;

                    $media = $mediaById[$mediaId];
                    $playlistMediaRepo->addMediaToPlaylist($media, $playlist, $weight);

                    $foundPaths++;
                }
            }

            $this->em->flush();
        }

        return $response->withJson(new Entity\Api\Status(
            true,
            __('Playlist successfully imported; %d of %d files were successfully matched.', $foundPaths, $totalPaths)
        ));
    }

    /**
     * @return mixed[]
     */
    protected function viewRecord($record, ServerRequest $request): array
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $song_totals = $this->em->createQuery(/** @lang DQL */ '
            SELECT count(sm.id) AS num_songs, sum(sm.length) AS total_length
            FROM App\Entity\StationMedia sm
            JOIN sm.playlists spm
            WHERE spm.playlist = :playlist')
            ->setParameter('playlist', $record)
            ->getArrayResult();

        $return['num_songs'] = (int)$song_totals[0]['num_songs'];
        $return['total_length'] = (int)$song_totals[0]['total_length'];

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links'] = [
            'toggle' => $router->fromHere('api:stations:playlist:toggle', ['id' => $record->getId()], [], !$isInternal),
            'order' => $router->fromHere('api:stations:playlist:order', ['id' => $record->getId()], [], !$isInternal),
            'reshuffle' => $router->fromHere(
                'api:stations:playlist:reshuffle',
                ['id' => $record->getId()],
                [],
                !$isInternal
            ),
            'import' => $router->fromHere('api:stations:playlist:import', ['id' => $record->getId()], [], !$isInternal),
            'self' => $router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], !$isInternal),
        ];

        foreach (['pls', 'm3u'] as $format) {
            $return['links']['export'][$format] = $router->fromHere(
                'api:stations:playlist:export',
                ['id' => $record->getId(), 'format' => $format],
                [],
                !$isInternal
            );
        }

        return $return;
    }

    /**
     * @return mixed[]
     */
    protected function toArray($record, array $context = []): array
    {
        return parent::toArray($record, array_merge($context, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['queue'],
        ]));
    }
}
