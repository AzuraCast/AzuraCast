<?php
namespace Controller\Api;

use Entity;

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
    public function listAction()
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

        return $this->returnSuccess($stations);
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
    public function indexAction()
    {
        try {
            $station = $this->getStation();
            return $this->returnSuccess($station->api($station->getFrontendAdapter($this->di)));
        } catch(\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}