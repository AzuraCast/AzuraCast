<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use OpenApi\Annotations as OA;

/**
 * @extends AbstractStationApiCrudController<Entity\SftpUser>
 */
class SftpUsersController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\SftpUser::class;
    protected string $resourceRouteName = 'api:stations:sftp-user';

    /**
     * @OA\Get(path="/station/{station_id}/sftp-users",
     *   tags={"Stations: SFTP Users"},
     *   description="List all current SFTP users.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/SftpUser"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/sftp-users",
     *   tags={"Stations: SFTP Users"},
     *   description="Create a new SFTP user.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/SftpUser")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/SftpUser")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/sftp-user/{id}",
     *   tags={"Stations: SFTP Users"},
     *   description="Retrieve details for a single SFTP user.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="SFTP User ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/SftpUser")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/sftp-user/{id}",
     *   tags={"Stations: SFTP Users"},
     *   description="Update details of a single SFTP user.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/SftpUser")
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
     * @OA\Delete(path="/station/{station_id}/sftp-user/{id}",
     *   tags={"Stations: SFTP Users"},
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
}
