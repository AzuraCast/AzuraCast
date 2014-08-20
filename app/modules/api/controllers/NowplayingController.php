<?php
use \Entity\Station;
use \Entity\Song;
use \Entity\Schedule;

class Api_NowplayingController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
        $np = \DF\Cache::get('api_nowplaying_data');

        if (!$np)
        {
            // Automatically generate new API info.
            $stations = Station::fetchAll();

            $np = array();

            foreach($stations as $station)
            {
                $short_name = $station->short_name;

                $np_data = $station->nowplaying_data;
                $np[$short_name] = \PVL\NowPlaying::processApi($np_data, $station);

                $np[$short_name]['cache'] = 'miss';
            }

            \DF\Cache::save($np, 'api_nowplaying_data', array('nowplaying'), 10);
        }

        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $station = Station::find($id);

            if (!($station instanceof Station))
            {
                return $this->returnError('Station not found!');
            }
            else
            {
                $sc = $station->getShortName();
                return $this->returnSuccess($np[$sc]);
            }
        }
        elseif ($this->hasParam('station'))
        {
            $short = $this->_getParam('station');
            if (isset($np[$short]))
                return $this->returnSuccess($np[$short]);
            else
                return $this->returnError('Station not found!');
        }
        else
        {
            return $this->returnSuccess($np);
        }
    }
}