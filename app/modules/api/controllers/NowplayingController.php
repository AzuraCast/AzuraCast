<?php
use \Entity\Station;
use \Entity\Song;
use \Entity\Schedule;

class Api_NowplayingController extends \PVL\Controller\Action\Api
{
    public function indexAction()
    {
        $file_path_api = DF_INCLUDE_STATIC.'/api/nowplaying_api.json';
        $np_raw = file_get_contents($file_path_api);

        if ($this->hasParam('id') || $this->hasParam('station'))
        {
            $np_arr = @json_decode($np_raw, TRUE);
            $np = $np_arr['result'];

            if ($this->hasParam('id'))
            {
                $id = (int)$this->getParam('id');
                foreach($np as $key => $station) {
                    if($station->id === $id) {
                        $sc = $key;
                        break;
                    }
                }
                return $this->returnError('Station not found!');
            }
            elseif ($this->hasParam('station'))
            {
                $sc = $this->getParam('station');
            }

            if (isset($np[$sc]))
                $this->returnSuccess($np[$sc]);
            else
                return $this->returnError('Station not found!');
        }
        else
        {
            $this->returnRaw($np_raw, 'json');
        }
    }
}