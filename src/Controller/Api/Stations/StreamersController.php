<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\ServerRequest;
use OpenApi\Annotations as OA;

class StreamersController extends AbstractStationApiCrudController
{
    protected $entityClass = Entity\StationStreamer::class;
    protected $resourceRouteName = 'api:stations:streamer';

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
     * @inheritDoc
     */
    protected function _getStation(ServerRequest $request): Entity\Station
    {
        $station = parent::_getStation($request);

        $backend = $request->getStationBackend();
        if (!$backend::supportsStreamers()) {
            throw new \App\Exception\StationUnsupported;
        }

        return $station;
    }
}
