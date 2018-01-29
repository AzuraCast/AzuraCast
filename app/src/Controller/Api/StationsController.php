<?php
namespace Controller\Api;

use Entity;
use App\Http\Request;
use App\Http\Response;

class StationsController extends BaseController
{
    /**
     * @SWG\Get(path="/stations",
     *   tags={"Stations: General"},
     *   description="Returns a list of stations.",
     *   parameters={},
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       type="array",
     *       @SWG\Items(ref="#/definitions/Station")
     *     )
     *   )
     * )
     */
    public function listAction(Request $request, Response $response): Response
    {
        $stations_raw = $this->em->getRepository(Entity\Station::class)->findAll();

        $stations = [];
        foreach ($stations_raw as $row) {
            /** @var Entity\Station $row */

            $api_row = $row->api($row->getFrontendAdapter($this->di));
            if ($api_row->is_public) {
                $stations[] = $api_row;
            }
        }

        return $response->withJson($stations);
    }

    /**
     * @SWG\Get(path="/station/{station_id}",
     *   tags={"Stations: General"},
     *   description="Return information about a single station.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       ref="#/definitions/Station"
     *     )
     *   ),
     *   @SWG\Response(response=404, description="Station not found")
     * )
     */
    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $api_response = $station->api($station->getFrontendAdapter($this->di));
        return $response->withJson($api_response);
    }
}