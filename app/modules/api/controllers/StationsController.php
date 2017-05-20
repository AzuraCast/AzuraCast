<?php
namespace Controller\Api;

use Entity;

class StationsController extends BaseController
{
    public function listAction()
    {
        $stations_raw = $this->em->getRepository(Entity\Station::class)->findAll();

        $stations = [];
        foreach ($stations_raw as $row) {
            $stations[] = Entity\Station::api($row, $this->di);
        }

        return $this->returnSuccess($stations);
    }

    public function indexAction()
    {
        try {
            $station = $this->getStation();
            return $this->returnSuccess(Entity\Station::api($station, $this->di));
        } catch(\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}