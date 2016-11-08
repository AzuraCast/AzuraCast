<?php
namespace Modules\Frontend\Controllers;

use Entity\Station;
use Entity\Settings;

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

        if (!$this->hasParam('station'))
        {
            $station = reset($stations);
            return $this->redirectFromHere(['station' => $station->id]);
        }

        $station_id = (int)$this->getParam('station');
        $station = $this->em->getRepository(Station::class)->find($station_id);

        if (!($station instanceof Station))
            throw new \App\Exception(_('Station not found!'));

        $this->view->station = $station;
    }
}