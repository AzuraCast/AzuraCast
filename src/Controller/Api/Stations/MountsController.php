<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\RequestHelper;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ServerRequestInterface;

class MountsController extends AbstractStationApiCrudController
{
    protected $entityClass = Entity\StationMount::class;
    protected $resourceRouteName = 'api:stations:mount';

    /**
     * @OA\Get(path="/station/{station_id}/mounts",
     *   tags={"Stations: Mount Points"},
     *   description="List all current mount points.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StationMount"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/mounts",
     *   tags={"Stations: Mount Points"},
     *   description="Create a new mount point.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/mount/{id}",
     *   tags={"Stations: Mount Points"},
     *   description="Retrieve details for a single mount point.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Streamer ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/mount/{id}",
     *   tags={"Stations: Mount Points"},
     *   description="Update details of a single mount point.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
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
     * @OA\Delete(path="/station/{station_id}/mount/{id}",
     *   tags={"Stations: Mount Points"},
     *   description="Delete a single mount point.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="StationMount ID",
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
    protected function _getStation(ServerRequestInterface $request): Entity\Station
    {
        $station = parent::_getStation($request);

        $frontend = RequestHelper::getStationFrontend($request);
        if (!$frontend::supportsMounts()) {
            throw new \App\Exception\StationUnsupported;
        }

        return $station;
    }
}
