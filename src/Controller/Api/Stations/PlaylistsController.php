<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Doctrine\Paginator;
use Azura\Exception;
use Azura\Http\RouterInterface;
use Cake\Chronos\Chronos;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlaylistsController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationPlaylist::class;
    protected string $resourceRouteName = 'api:stations:playlist';

    protected Entity\Repository\StationPlaylistScheduleRepository $playlistScheduleRepo;

    public function __construct(
        EntityManager $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        Entity\Repository\StationPlaylistScheduleRepository $playlistScheduleRepo
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->playlistScheduleRepo = $playlistScheduleRepo;
    }

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

        $paginator = new Paginator($qb);
        $paginator->setFromRequest($request);

        $router = $request->getRouter();

        $paginator->setPostprocessor(function ($row) use ($router) {
            return $this->_viewRecord($row, $router);
        });

        return $paginator->write($response);
    }

    /**
     * Controller used to respond to AJAX requests from the playlist "Schedule View".
     *
     * @param ServerRequest $request
     * @param Response $response
     *
     * @return ResponseInterface
     */
    public function scheduleAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $tz = new DateTimeZone($station->getTimezone());

        $params = $request->getQueryParams();

        $startDateStr = substr($params['start'], 0, 10);
        $startDate = Chronos::createFromFormat('Y-m-d', $startDateStr, $tz)->subDay();

        $endDateStr = substr($params['end'], 0, 10);
        $endDate = Chronos::createFromFormat('Y-m-d', $endDateStr, $tz);

        /** @var Entity\StationPlaylist[] $all_playlists */
        $playlists = $station->getPlaylists()->filter(function ($record) {
            /** @var Entity\StationPlaylist $record */
            return (!$record->isJingle() && $record->getScheduleItems()->count() > 0);
        });

        $events = [];
        foreach ($playlists as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            foreach ($playlist->getScheduleItems() as $scheduleItem) {
                /** @var Entity\StationPlaylistSchedule $scheduleItem */
                $i = $startDate;
                while ($i <= $endDate) {
                    $dayOfWeek = $i->format('N');

                    if ($scheduleItem->shouldPlayOnCurrentDate($i)
                        && $scheduleItem->isScheduledToPlayToday($dayOfWeek)) {
                        $playlistStart = Entity\StationPlaylist::getDateTime($scheduleItem->getStartTime(), $i);
                        $playlistEnd = Entity\StationPlaylist::getDateTime($scheduleItem->getEndTime(), $i);

                        // Handle overnight playlists
                        if ($playlistEnd < $playlistStart) {
                            $playlistEnd = $playlistEnd->addDay();
                        }

                        $events[] = [
                            'id' => $playlist->getId(),
                            'title' => $playlist->getName(),
                            'start' => $playlistStart->toIso8601String(),
                            'end' => $playlistEnd->toIso8601String(),
                            'edit_url' => (string)$request->getRouter()->named(
                                'api:stations:playlist',
                                ['station_id' => $station->getId(), 'id' => $playlist->getId()]
                            ),
                        ];
                    }

                    $i = $i->addDay();
                }
            }
        }

        return $response->withJson($events);
    }

    public function getOrderAction(
        ServerRequest $request,
        Response $response,
        $id
    ): ResponseInterface {
        $record = $this->_getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        if ($record->getSource() !== Entity\StationPlaylist::SOURCE_SONGS
            || $record->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL) {
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
        $record = $this->_getRecord($request->getStation(), $id);

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        if ($record->getSource() !== Entity\StationPlaylist::SOURCE_SONGS
            || $record->getOrder() !== Entity\StationPlaylist::ORDER_SEQUENTIAL) {
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
        $record = $this->_getRecord($request->getStation(), $id);

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
        $record = $this->_getRecord($request->getStation(), $id);

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

    protected function _viewRecord($record, RouterInterface $router)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->_normalizeRecord($record);

        $song_totals = $this->em->createQuery(/** @lang DQL */ '
            SELECT count(sm.id) AS num_songs, sum(sm.length) AS total_length
            FROM App\Entity\StationMedia sm
            JOIN sm.playlists spm
            WHERE spm.playlist = :playlist')
            ->setParameter('playlist', $record)
            ->getArrayResult();

        $return['num_songs'] = (int)$song_totals[0]['num_songs'];
        $return['total_length'] = (int)$song_totals[0]['total_length'];

        $return['links'] = [
            'toggle' => $router->fromHere('api:stations:playlist:toggle', ['id' => $record->getId()], [], true),
            'order' => $router->fromHere('api:stations:playlist:order', ['id' => $record->getId()], [], true),
            'self' => $router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], true),
        ];

        foreach (['pls', 'm3u'] as $format) {
            $return['links']['export'][$format] = $router->fromHere(
                'api:stations:playlist:export',
                ['id' => $record->getId(), 'format' => $format],
                [],
                true
            );
        }

        return $return;
    }

    protected function _normalizeRecord($record, array $context = [])
    {
        return parent::_normalizeRecord($record, array_merge($context, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['queue'],
        ]));
    }

    /**
     * @inheritDoc
     */
    protected function _denormalizeToRecord($data, $record = null, array $context = []): object
    {
        $scheduleItems = $data['schedule_items'] ?? null;
        unset($data['schedule_items']);

        $record = parent::_denormalizeToRecord($data, $record, $context);

        if ($record instanceof Entity\StationPlaylist) {
            $this->em->persist($record);
            $this->em->flush($record);

            if (null !== $scheduleItems) {
                $this->playlistScheduleRepo->setScheduleItems($record, $scheduleItems);
            }
        }

        return $record;
    }
}
