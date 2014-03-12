<?php
use \Entity\Station;

class Api_NowplayingController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
    	if ($this->_hasParam('id'))
    	{
    		$id = (int)$this->_getParam('id');
    		$station = Station::find($id);

    		if (!($station instanceof Station))
    			return $this->returnError('Station not found!');
    		else
    			return $this->returnSuccess($station->nowplaying_data);
    	}
    	elseif ($this->_hasParam('station'))
    	{
    		$short_names = Station::getShortNameLookup(true);
    		$short = $this->_getParam('station');

    		if (isset($short_names[$short]))
    		{
    			$data = $short_names[$short];
    			return $this->returnSuccess($data['nowplaying_data']);
    		}
    		else
    		{
    			return $this->returnError('Station not found!');
    		}
    	}
    	else
    	{
    		$return_raw = $this->em->createQuery('SELECT s.name, s.nowplaying_data FROM Entity\Station s WHERE s.is_active = 1 ORDER BY s.weight ASC')
    			->getArrayResult();

    		$np = array();
    		foreach($return_raw as $row)
    		{
    			$short_name = Station::getStationShortName($row['name']);
    			$np[$short_name] = $row['nowplaying_data'];
    		}

    		return $this->returnSuccess($np);
    	}
    }
}