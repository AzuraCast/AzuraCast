<?php
namespace Controller\Api;

use Entity\Station;

class StationsController extends BaseController
{
    public function indexAction()
    {
        if ($this->hasParam('station')) {
            $record = $this->em->getRepository(Station::class)->findByShortCode($this->getParam('station'));
        } elseif ($this->hasParam('id')) {
            $id = (int)$this->getParam('id');
            $record = $this->em->getRepository(Station::class)->find($id);
        } else {
            $this->dispatcher->forward([
                'controller' => 'station',
                'action' => 'list',
            ]);

            return false;
        }

        if (!($record instanceof Station) || $record->deleted_at) {
            return $this->returnError('Station not found.');
        }

        return $this->returnSuccess(Station::api($record, $this->di));
    }

    public function viewAction()
    {
        return $this->indexAction();
    }

    public function listAction()
    {
        $stations_raw = $this->em->getRepository(Station::class)->findAll();

        $stations = [];
        foreach ($stations_raw as $row) {
            $stations[] = Station::api($row, $this->di);
        }

        return $this->returnSuccess($stations);
    }
}