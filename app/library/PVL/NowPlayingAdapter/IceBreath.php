<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class IceBreath extends AdapterAbstract
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
            $stream = $return['result']['server_streams'][0];

            $np['listeners_unique'] = (int)$stream['stream_listeners_unique'];
            $np['listeners_total'] = (int)$stream['stream_listeners'];
            $np['listeners'] = $this->getListenerCount($np['listeners_unique'], $np['listeners_total']);

            $np['artist'] = $stream['stream_nowplaying']['artist'];
            $np['title'] = $stream['stream_nowplaying']['song'];
            $np['text'] = $stream['stream_nowplaying']['text'];

            return $np;
        }

        return false;
    }
}