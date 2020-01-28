<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
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

        $query = $this->em->createQuery(/** @lang DQL */'SELECT ssb 
            FROM App\Entity\StationStreamerBroadcast ssb
            WHERE ssb.station = :station AND ssb.streamer = :streamer
            ORDER BY ssb.timestampEnd DESC')
            ->setParameter('station', $station)
            ->setParameter('streamer', $streamer);

        $paginator = new Paginator($query);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();

        $paginator->setPostprocessor(function ($row) use ($is_bootgrid) {
            $return = $this->_normalizeRecord($row);
            if ($is_bootgrid) {
                return Utilities::flattenArray($return, '_');
            }

            return $return;
        });

        return $paginator->write($response);
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
