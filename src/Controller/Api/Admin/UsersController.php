<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use App\Controller\Api\AbstractGenericCrudController;

/**
 * @see \App\Provider\ApiProvider
 */
class UsersController extends AbstractGenericCrudController
{
    protected $entityClass = Entity\User::class;
    protected $resourceRouteName = 'api:admin:user';

    /**
     * @OA\Get(path="/station/{station_id}/listeners",
     *   tags={"Stations: Listeners"},
     *   description="Return detailed information about current listeners.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Api_Listener")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={
     *     {"api_key"}
     *   },
     * )
     */
}
