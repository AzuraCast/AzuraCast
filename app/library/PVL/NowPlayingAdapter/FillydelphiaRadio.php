<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class FillydelphiaRadio extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process($np)
    {
        $return_raw = $this->getUrl();

        if (!$return_raw)
            return false;

        $return = @json_decode($return_raw, true);

        if ($return['result'])
        {
            $u_list = (int)$return['result']['server_listeners_unique'];
            $c_list = (int)$return['result']['server_listeners_total'];
            $np['listeners'] = $this->getListenerCount($u_list, $c_list);

            $np_stream = $return['result']['server_streams'][0]['stream_nowplaying'];
            $np['artist'] = $np_stream['artist'];
            $np['title'] = $np_stream['song'];
            $np['text'] = $np_stream['text'];
            return $np;
        }

        return false;
    }
}