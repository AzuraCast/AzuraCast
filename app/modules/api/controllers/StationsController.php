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
            /** @var Entity\Station $row */
            $stations[] = $row->api($row->getFrontendAdapter($this->di));
        }

        return $this->returnSuccess($stations);
    }

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