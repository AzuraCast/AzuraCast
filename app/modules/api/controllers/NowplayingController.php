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
            $return_raw = Station::fetchArray();

            $np = array();
            foreach($return_raw as $row)
            {
                $np_row = $this->_processRow($row);
                $short_name = $np_row['station']['shortcode'];

                $np[$short_name] = $np_row;
            }

            \DF\Cache::save($np, 'api_nowplaying_data', array(), 10);
        }

        if ($this->_hasParam('id'))
        {
            $id = (int)$this->_getParam('id');
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
        elseif ($this->_hasParam('station'))
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

    protected function _processRow($row)
    {
        $np = array();
        $np_raw = $row['nowplaying_data'];

        $np['status'] = $np_raw['status'];
        $np['station'] = Station::api($row);

        $np['listeners'] = array(
            'current'       => $np_raw['listeners'],
            'unique'        => $np_raw['listeners_unique'],
            'total'         => $np_raw['listeners_total'],
        );

        $vote_functions = array('like', 'dislike', 'clearvote');
        $vote_urls = array();

        foreach($vote_functions as $vote_function)
            $vote_urls[$vote_function] = \DF\Url::route(array('module' => 'api', 'controller' => 'song', 'action' => $vote_function, 'sh_id' => $np_raw['song_sh_id']));

        $current_song = array(
            'id'        => $np_raw['song_id'],
            'text'      => $np_raw['text'],
            'artist'    => $np_raw['artist'],
            'title'     => $np_raw['title'],

            'score'     => $np_raw['song_score'],
            'sh_id'     => $np_raw['song_sh_id'],
            'vote_urls' => $vote_urls,

            'external'  => $np_raw['song_external'],
        );

        $np['current_song'] = $current_song;

        foreach((array)$np_raw['song_history'] as $song_row)
        {
            $np['song_history'][] = array(
                'played_at' => $song_row['timestamp'],
                'song'      => Song::api($song_row),
            );
        }

        $np['event'] = Schedule::api($np_raw['event']);
        $np['event_upcoming'] = Schedule::api($np_raw['event_upcoming']);

        return $np;
    }
}