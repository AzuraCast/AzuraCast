<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServicesController
{
    /** @var EntityManager */
    protected $em;

    /** @var Configuration */
    protected $configuration;

    public function __construct(EntityManager $em, Configuration $configuration)
    {
        $this->em = $em;
        $this->configuration = $configuration;
    }

    /**
     * @OA\Get(path="/station/{station_id}/status",
     *   tags={"Stations: Service Control"},
     *   description="Retrieve the current status of all serivces associated with the radio broadcast.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Api_StationServiceStatus")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function statusAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        $backend = RequestHelper::getStationBackend($request);
        $frontend = RequestHelper::getStationFrontend($request);

        return ResponseHelper::withJson($response, new Entity\Api\StationServiceStatus(
            $backend->isRunning($station),
            $frontend->isRunning($station)
        ));
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function restartAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $this->configuration->writeConfiguration($station, false, true);

        return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s restarted.', __('Station'))));
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
     *     content="restart",
     *     required=false,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Api_Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string|int $station_id
     * @param string $do
     * @return ResponseInterface
     */
    public function frontendAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $do = 'restart'): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $frontend = RequestHelper::getStationFrontend($request);

        switch ($do) {
            case 'stop':
                $frontend->stop($station);

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s stopped.', __('Frontend'))));
            break;

            case 'start':
                $frontend->start($station);

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s started.', __('Frontend'))));
            break;

            case 'restart':
            default:
                try
                {
                    $frontend->stop($station);
                } catch(\App\Exception\Supervisor\NotRunning $e) {}

                $frontend->write($station);
                $frontend->start($station);

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s restarted.', __('Frontend'))));
            break;
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
     *     content="restart",
     *     required=false,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Api_Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Api_Error")),
     *   security={{"api_key": {}}}
     * )
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string|int $station_id
     * @param string $do
     * @return ResponseInterface
     */
    public function backendAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $do = 'restart'): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $backend = RequestHelper::getStationBackend($request);

        switch ($do) {
            case 'skip':
                if (method_exists($backend, 'skip')) {
                    $backend->skip($station);
                }

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('Song skipped.')));
            break;

            case 'disconnect':
                if (method_exists($backend, 'disconnectStreamer')) {
                    $backend->disconnectStreamer($station);
                }

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('Streamer disconnected.')));
            break;

            case 'stop':
                $backend->stop($station);

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s stopped.', __('Backend'))));
                break;

            case 'start':
                $backend->start($station);

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s started.', __('Backend'))));
                break;

            case 'restart':
            default:
                try
                {
                    $backend->stop($station);
                } catch(\App\Exception\Supervisor\NotRunning $e) {}

                $backend->write($station);
                $backend->start($station);

                return ResponseHelper::withJson($response, new Entity\Api\Status(true, __('%s restarted.', __('Backend'))));
                break;
        }
    }

}
