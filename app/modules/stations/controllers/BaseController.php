<?php
namespace Modules\Stations\Controllers;

use Entity\Station;

class BaseController extends \App\Mvc\Controller
{
    /**
     * @var Station The current active station.
     */
    protected $station;

    public function init()
    {
        $station_id = (int)$this->getParam('station');
        $this->station = $this->view->station = $this->em->getRepository(Station::class)->find($station_id);

        if (!($this->station instanceof Station))
            throw new \App\Exception\PermissionDenied;

        parent::init();
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('view station management', $this->station->id);
    }
}