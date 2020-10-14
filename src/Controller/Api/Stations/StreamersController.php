<?php

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class StreamersController extends AbstractScheduledEntityController
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
     * Controller used to respond to AJAX requests from the streamer "Schedule View".
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function scheduleAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $scheduleItems = $this->em->createQuery(/** @lang DQL */ 'SELECT
            ssc, sst
            FROM App\Entity\StationSchedule ssc
            LEFT JOIN ssc.streamer sst
            WHERE sst.station = :station AND sst.is_active = 1
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
                /** @var Entity\StationStreamer $streamer */
                $streamer = $scheduleItem->getStreamer();

                return [
                    'id' => $streamer->getId(),
                    'title' => $streamer->getDisplayName(),
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    'edit_url' => (string)$request->getRouter()->named(
                        'api:stations:streamer',
                        ['station_id' => $station->getId(), 'id' => $streamer->getId()]
                    ),
                ];
            }
        );
    }

    /**
     * @param mixed $record
     * @param ServerRequest $request
     *
     * @return mixed[]
     */
    protected function viewRecord($record, ServerRequest $request): array
    {
        $return = parent::viewRecord($record, $request);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();

        $return['links']['broadcasts'] = $router->fromHere(
            'api:stations:streamer:broadcasts',
            ['id' => $record->getId()],
            [],
            !$isInternal
        );

        return $return;
    }

    /**
     * @inheritDoc
     */
    protected function getStation(ServerRequest $request): Entity\Station
    {
        $station = parent::getStation($request);

        $backend = $request->getStationBackend();
        if (!$backend::supportsStreamers()) {
            throw new StationUnsupportedException();
        }

        return $station;
    }
}
