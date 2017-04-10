<?php
namespace Controller\Frontend;

use Entity\Station;

class PublicController extends BaseController
{
    public function permissions()
    {
        return true;
    }

    public function indexAction()
    {
        // Inject all stations.
        $stations = $this->em->getRepository(Station::class)->findAll();
        $this->view->stations = $stations;

        if (!$this->hasParam('station')) {
            $station = reset($stations);
            return $this->redirectFromHere(['station' => $station->id]);
        }

        $this->view->station = $this->_getStation();
    }

    public function embedAction()
    {
        $this->view->station = $this->_getStation();
    }

    public function embedrequestsAction()
    {
        $this->view->station = $this->_getStation();
    }

    protected function _getStation()
    {
        $station_id = (int)$this->getParam('station');
        $station = $this->em->getRepository(Station::class)->find($station_id);

        if (!($station instanceof Station)) {
            throw new \App\Exception(_('Station not found!'));
        }

        return $station;
    }
}