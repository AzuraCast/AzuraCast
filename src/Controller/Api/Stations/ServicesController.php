<?php
namespace App\Controller\Api\Stations;

use App\Http\Request;
use App\Http\Response;
use App\Radio;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Psr\Http\Message\ResponseInterface;

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
     * @OA\Post(path="/station/{station_id}/restart",
     *   tags={"Stations: Service Control"},
     *   description="Restart all services associated with the radio broadcast.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Error")),
     *   security={
     *     {"api_key": {"manage station broadcasting"}}
     *   }
     * )
     */
    public function restartAction(Request $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();

        $this->configuration->writeConfiguration($station);

        try
        {
            $backend->stop($station);
        } catch(\App\Exception\Supervisor\NotRunning $e) {}

        try
        {
            $frontend->stop($station);
        } catch(\App\Exception\Supervisor\NotRunning $e) {}

        $frontend->start($station);
        $backend->start($station);

        $station->setHasStarted(true);
        $station->setNeedsRestart(false);

        $this->em->persist($station);
        $this->em->flush();

        return $response->withJson(new Entity\Api\Status(true, __('%s restarted.', __('Station'))));
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
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Error")),
     *   security={
     *     {"api_key": {"manage station broadcasting"}}
     *   }
     * )
     */
    public function frontendAction(Request $request, Response $response, $station_id, $do = 'restart'): ResponseInterface
    {
        $station = $request->getStation();
        $frontend = $request->getStationFrontend();

        switch ($do) {
            case "stop":
                $frontend->stop($station);

                return $response->withJson(new Entity\Api\Status(true, __('%s stopped.', __('Frontend'))));
            break;

            case "start":
                $frontend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('%s started.', __('Frontend'))));
            break;

            case "restart":
            default:
                try
                {
                    $frontend->stop($station);
                } catch(\App\Exception\Supervisor\NotRunning $e) {}

                $frontend->write($station);
                $frontend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('%s restarted.', __('Frontend'))));
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
     *     description="The action to perform (start, stop, restart)",
     *     in="path",
     *     content="restart",
     *     required=false,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success", @OA\Schema(ref="#/components/schemas/Status")),
     *   @OA\Response(response=403, description="Access Forbidden", @OA\Schema(ref="#/components/schemas/Error")),
     *   security={
     *     {"api_key": {"manage station broadcasting"}}
     *   }
     * )
     */
    public function backendAction(Request $request, Response $response, $station_id, $do = 'restart'): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        switch ($do) {
            case "skip":
                if (method_exists($backend, 'skip')) {
                    $backend->skip($station);
                }

                return $response->withJson(new Entity\Api\Status(true, __('Song skipped.')));
            break;

            case "disconnect":
                if (method_exists($backend, 'disconnectStreamer')) {
                    $backend->disconnectStreamer($station);
                }

                return $response->withJson(new Entity\Api\Status(true, __('Streamer disconnected.')));
            break;

            case "stop":
                $backend->stop($station);

                return $response->withJson(new Entity\Api\Status(true, __('%s stopped.', __('Backend'))));
                break;

            case "start":
                $backend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('%s started.', __('Backend'))));
                break;

            case "restart":
            default:
                try
                {
                    $backend->stop($station);
                } catch(\App\Exception\Supervisor\NotRunning $e) {}

                $backend->write($station);
                $backend->start($station);

                return $response->withJson(new Entity\Api\Status(true, __('%s restarted.', __('Backend'))));
                break;
        }
    }

}
