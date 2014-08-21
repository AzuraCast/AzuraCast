<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class TheHiveRadio extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process($np)
    {
        $return_raw = $this->getUrl();

        if (!$return_raw)
            return false;

        $return = @json_decode($return_raw, true);

        if (!empty($return['result']))
        {
            $u_list = (int)$return['result']['server_listeners_unique'];
            $c_list = (int)$return['result']['server_listeners_total'];
            $np['listeners'] = $this->getListenerCount($u_list, $c_list);

            foreach((array)$return['result']['server_streams'] as $stream)
            {
                if ($stream['stream_name'] == '/normal.mp3')
                {
                    $np['artist'] = $stream['stream_nowplaying']['artist'];
                    $np['title'] = $stream['stream_nowplaying']['song'];
                    $np['text'] = $stream['stream_nowplaying']['text'];
                    return $np;
                }
            }
        }

        return false;
    }
}