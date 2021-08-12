<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\PermissionDeniedException;
use App\Http\ServerRequest;
use InvalidArgumentException;
use OpenApi\Annotations as OA;

/**
 * @extends AbstractStationApiCrudController<Entity\StationRemote>
 */
class RemotesController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationRemote::class;
    protected string $resourceRouteName = 'api:stations:remote';

    /**
     * @OA\Get(path="/station/{station_id}/remotes",
     *   tags={"Stations: Remote Relays"},
     *   description="List all current remote relays.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_StationRemote"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/remotes",
     *   tags={"Stations: Remote Relays"},
     *   description="Create a new remote relay.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Api_StationRemote")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_StationRemote")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/remote/{id}",
     *   tags={"Stations: Remote Relays"},
     *   description="Retrieve details for a single remote relay.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Remote Relay ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_StationRemote")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/remote/{id}",
     *   tags={"Stations: Remote Relays"},
     *   description="Update details of a single remote relay.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Api_StationRemote")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Remote Relay ID",
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
     * @OA\Delete(path="/station/{station_id}/remote/{id}",
     *   tags={"Stations: Remote Relays"},
     *   description="Delete a single remote relay.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Remote Relay ID",
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

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof Entity\StationRemote)) {
            throw new InvalidArgumentException(
                sprintf('Record must be an instance of %s.', Entity\StationRemote::class)
            );
        }

        $returnArray = $this->toArray($record);

        $return = new Entity\Api\StationRemote();
        $return->fromParentObject($returnArray);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return->is_editable = $record->isEditable();

        $return->links = [
            'self' => (string)$router->fromHere(
                route_name:   $this->resourceRouteName,
                route_params: ['id' => $record->getIdRequired()],
                absolute:     !$isInternal
            ),
        ];

        return $return;
    }

    /**
     * @inheritDoc
     */
    protected function getRecord(Entity\Station $station, int|string $id): ?object
    {
        $record = parent::getRecord($station, $id);

        if ($record instanceof Entity\StationRemote && !$record->isEditable()) {
            throw new PermissionDeniedException('This record cannot be edited.');
        }

        return $record;
    }
}
