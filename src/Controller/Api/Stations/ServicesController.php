<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\Supervisor\NotRunningException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class ServicesController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Configuration $configuration
    ) {
    }

    /**
     * @OA\Get(path="/station/{station_id}/status",
     *   tags={"Stations: Service Control"},
     *   description="Retrieve the current status of all serivces associated with the radio broadcast.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(ref="#/components/schemas/Api_StationServiceStatus")
     *   ),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function statusAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        return $response->withJson(
            new Entity\Api\StationServiceStatus(
                $backend->isRunning($station),
                $frontend->isRunning($station)
            )
        );
    }

    /**
     * @OA\Post(path="/station/{station_id}/restart",
     *   tags={"Stations: Service Control"},
     *   description="Restart all services associated with the radio broadcast.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Api_Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function restartAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $this->configuration->writeConfiguration($station, true);

        return $response->withJson(new Entity\Api\Status(true, __('Station restarted.')));
    }

    /**
     * @OA\Post(path="/station/{station_id}/frontend/{action}",
     *   tags={"Stations: Service Control"},
     *   description="Perform service control actions on the radio frontend (Icecast, SHOUTcast, etc.)",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="action",
     *     description="The action to perform (start, stop, restart)",
     *     in="path",
     *     required=false,
     *     @OA\Schema(
     *         type="string",
     *         default="restart"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Api_Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param string $do
     */
    public function frontendAction(
        ServerRequest $request,
        Response $response,
        $do = 'restart'
    ): ResponseInterface {
        $station = $request->getStation();
        $frontend = $request->getStationFrontend();

        switch ($do) {
            case 'stop':
                $frontend->stop($station);

                return $response->withJson(new Entity\Api\Status(true, __('Frontend stopped.')));

            case 'start':
                $frontend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('Frontend started.')));

            case 'restart':
            default:
                try {
                    $frontend->stop($station);
                } catch (NotRunningException) {
                }

                $frontend->write($station);
                $frontend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('Frontend restarted.')));
        }
    }

    /**
     * @OA\Post(path="/station/{station_id}/backend/{action}",
     *   tags={"Stations: Service Control"},
     *   description="Perform service control actions on the radio backend (Liquidsoap)",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="action",
     *     description="The action to perform (for all: start, stop, restart; for Liquidsoap only: skip, disconnect)",
     *     in="path",
     *     required=false,
     *     @OA\Schema(
     *         type="string",
     *         default="restart"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Api_Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param AutoDJ $autodj
     * @param string $do
     */
    public function backendAction(
        ServerRequest $request,
        Response $response,
        AutoDJ $autodj,
        $do = 'restart'
    ): ResponseInterface {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        switch ($do) {
            case 'skip':
                if ($backend instanceof Liquidsoap) {
                    // Automatically queue the "next" song in the request queue.
                    if (!$station->useManualAutoDJ()) {
                        $nextSong = $autodj->annotateNextSong($station, true);
                        if (!empty($nextSong)) {
                            $backend->enqueue($station, $nextSong);
                        }
                    }

                    $backend->skip($station);
                }

                return $response->withJson(new Entity\Api\Status(true, __('Song skipped.')));

            case 'disconnect':
                if ($backend instanceof Liquidsoap) {
                    $backend->disconnectStreamer($station);
                }

                return $response->withJson(new Entity\Api\Status(true, __('Streamer disconnected.')));

            case 'stop':
                $backend->stop($station);

                return $response->withJson(new Entity\Api\Status(true, __('Backend stopped.')));

            case 'start':
                $backend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('Backend started.')));

            case 'restart':
            default:
                try {
                    $backend->stop($station);
                } catch (NotRunningException) {
                }

                $backend->write($station);
                $backend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('Backend restarted.')));
        }
    }
}
