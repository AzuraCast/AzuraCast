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
            $np['listeners'] = (int)$return['result']['server_listener_total'];

            $np_stream = $return['result']['server_streams'][0];
            $np['artist'] = $np_stream['artist'];
            $np['title'] = $np_stream['song'];
            $np['text'] = $np_stream['artist'].' - '.$np_stream['song'];
            return $np;
        }

        return false;
    }
}