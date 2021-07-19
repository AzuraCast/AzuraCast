<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use OpenApi\Annotations as OA;

/**
 * @extends AbstractStationApiCrudController<Entity\StationWebhook>
 */
class WebhooksController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationWebhook::class;
    protected string $resourceRouteName = 'api:stations:webhook';

    /**
     * @OA\Get(path="/station/{station_id}/webhooks",
     *   tags={"Stations: Web Hooks"},
     *   description="List all current web hooks.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StationWebhook"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/webhooks",
     *   tags={"Stations: Web Hooks"},
     *   description="Create a new web hook.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationWebhook")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationWebhook")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/webhook/{id}",
     *   tags={"Stations: Web Hooks"},
     *   description="Retrieve details for a single web hook.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Web Hook ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationWebhook")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/webhook/{id}",
     *   tags={"Stations: Web Hooks"},
     *   description="Update details of a single web hook.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationWebhook")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Web Hook ID",
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
     * @OA\Delete(path="/station/{station_id}/webhook/{id}",
     *   tags={"Stations: Web Hooks"},
     *   description="Delete a single web hook relay.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Web Hook ID",
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
