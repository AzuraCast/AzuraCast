<?php
namespace Controller\Frontend;

use Entity;

class PublicController extends BaseController
{
    public function permissions()
    {
        return true;
    }

    public function preDispatch()
    {
        $this->view->station = $this->_getStation();
    }

    public function indexAction()
    {}

    public function embedAction()
    {}

    public function embedrequestsAction()
    {}

    protected function _getStation()
    {
        $station_id = $this->getParam('station');

        /** @var Entity\Repository\StationRepository $station_repo */
        $station_repo = $this->em->getRepository(Entity\Station::class);

        if (is_numeric($station_id)) {
            $station = $station_repo->find($station_id);
        } else {
            $station = $station_repo->findByShortCode($station_id);
        }

        if (!($station instanceof Entity\Station)) {
            throw new \App\Exception(_('Station not found!'));
        }

        return $station;
    }
}