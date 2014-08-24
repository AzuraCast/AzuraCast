<?php
use \Entity\Station;

class Api_StationController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
        if ($this->_hasParam('station'))
        {
            $record = Station::findByShortCode($this->_getParam('station'));
        }
        elseif ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');
            $record = Station::find($id);
        }

        if (!($record instanceof Station) || $record->deleted_at)
            return $this->returnError('Station not found.');

        return $this->returnSuccess(Station::api($record));
    }

    public function listAction()
    {
        $category = $this->getParam('category', 'all');

        if ($category == 'all')
        {
            $stations_raw = Station::fetchArray();
        }
        else
        {
            $cats = Station::getStationsInCategories();

            if (!isset($cats[$category]))
                return $this->returnError('Category not found.');

            $stations_raw = $cats[$category]['stations'];
        }

        $stations = array();
        foreach($stations_raw as $row)
            $stations[] = Station::api($row);

        return $this->returnSuccess($stations);
    }
}