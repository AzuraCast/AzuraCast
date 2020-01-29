<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use App\Utilities;
use Azura\Doctrine\Paginator;
use Azura\Http\RouterInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class StreamersController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationStreamer::class;
    protected string $resourceRouteName = 'api:stations:streamer';

    /**
     * @OA\Get(path="/station/{station_id}/streamers",
     *   tags={"Stations: Streamers/DJs"},
     *   description="List all current Streamer/DJ accounts for the specified station.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StationStreamer"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/streamers",
     *   tags={"Stations: Streamers/DJs"},
     *   description="Create a new Streamer/DJ account.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationStreamer")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationStreamer")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/streamer/{id}",
     *   tags={"Stations: Streamers/DJs"},
     *   description="Retrieve details for a single Streamer/DJ account.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Streamer ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationStreamer")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/streamer/{id}",
     *   tags={"Stations: Streamers/DJs"},
     *   description="Update details of a single Streamer/DJ account.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationStreamer")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Streamer ID",
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
     * @OA\Delete(path="/station/{station_id}/streamer/{id}",
     *   tags={"Stations: Streamers/DJs"},
     *   description="Delete a single Streamer/DJ account.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="StationStreamer ID",
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
     * @param ServerRequest $request
     * @param Response $response
     * @param string|int $station_id
     * @param int $id
     *
     * @return ResponseInterface
     */
    public function broadcastsAction(
        ServerRequest $request,
        Response $response,
        $station_id,
        $id
    ): ResponseInterface {
        $station = $this->_getStation($request);
        $streamer = $this->_getRecord($station, $id);

        if (null === $streamer) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT ssb 
            FROM App\Entity\StationStreamerBroadcast ssb
            WHERE ssb.station = :station AND ssb.streamer = :streamer
            ORDER BY ssb.timestampStart DESC')
            ->setParameter('station', $station)
            ->setParameter('streamer', $streamer);

        $paginator = new Paginator($query);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function ($row) use ($is_bootgrid, $router) {
            /** @var Entity\StationStreamerBroadcast $row */
            $return = $this->_normalizeRecord($row);

            if (!empty($row->getRecordingPath())) {
                $return['links'] = [
                    'download' => $router->fromHere(
                        'api:stations:streamer:broadcast:download',
                        ['broadcast_id' => $row->getId()],
                        [],
                        true
                    ),
                ];
            }

            if ($is_bootgrid) {
                return Utilities::flattenArray($return, '_');
            }

            return $return;
        });

        return $paginator->write($response);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param Filesystem $filesystem
     * @param string|int $station_id
     * @param int $id
     * @param int $broadcast_id
     *
     * @return ResponseInterface
     */
    public function downloadBroadcastAction(
        ServerRequest $request,
        Response $response,
        Filesystem $filesystem,
        $station_id,
        $id,
        $broadcast_id
    ): ResponseInterface {
        $station = $this->_getStation($request);
        $streamer = $this->_getRecord($station, $id);

        /** @var Entity\StationStreamerBroadcast|null $broadcast */
        $broadcast = $this->em->getRepository(Entity\StationStreamerBroadcast::class)->findOneBy([
            'id' => $broadcast_id,
            'station' => $station,
            'streamer' => $streamer,
        ]);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $recordingPath = $broadcast->getRecordingPath();

        if (empty($recordingPath)) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, __('No recording available.')));
        }

        $fs = $filesystem->getForStation($station);
        $filename = basename($recordingPath);

        $recordingPath = 'recordings://' . $recordingPath;
        $fh = $fs->readStream($recordingPath);
        $fileMeta = $fs->getMetadata($recordingPath);

        try {
            $fileMime = $fs->getMimetype($recordingPath);
        } catch (\Exception $e) {
            $fileMime = 'application/octet-stream';
        }

        return $response->withFileDownload($fh, $filename, $fileMime)
            ->withHeader('Content-Length', $fileMeta['size'])
            ->withHeader('X-Accel-Buffering', 'no');
    }

    /**
     * @inheritDoc
     */
    protected function _viewRecord($record, RouterInterface $router)
    {
        $return = parent::_viewRecord($record, $router);
        $return['links']['broadcasts'] = $router->fromHere(
            'api:stations:streamer:broadcasts',
            ['id' => $record->getId()],
            [],
            true
        );

        return $return;
    }

    /**
     * @inheritDoc
     */
    protected function _getStation(ServerRequest $request): Entity\Station
    {
        $station = parent::_getStation($request);

        $backend = $request->getStationBackend();
        if (!$backend::supportsStreamers()) {
            throw new StationUnsupportedException;
        }

        return $station;
    }
}
