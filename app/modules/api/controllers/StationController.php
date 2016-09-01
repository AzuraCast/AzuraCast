<?php
namespace Modules\Api\Controllers;

use \Entity\Station;

class StationController extends BaseController
{
    public function indexAction()
    {
        if ($this->hasParam('station'))
        {
            $record = Station::findByShortCode($this->getParam('station'));
        }
        elseif ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $record = Station::find($id);
        }
        else
        {
            $this->dispatcher->forward(array(
                'controller' => 'station',
                'action' => 'list',
            ));
            return false;
        }

        if (!($record instanceof Station) || $record->deleted_at)
            return $this->returnError('Station not found.');

        return $this->returnSuccess(Station::api($record));
    }

    public function viewAction()
    {
        return $this->indexAction();
    }

    public function listAction()
    {
        $stations_raw = Station::fetchArray();

        $stations = array();
        foreach($stations_raw as $row)
            $stations[] = Station::api($row);

        return $this->returnSuccess($stations);
    }
}