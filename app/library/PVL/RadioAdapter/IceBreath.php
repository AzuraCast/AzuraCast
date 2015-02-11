<?php
namespace PVL\RadioAdapter;

use \Entity\Station;

class IceBreath extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $return = @json_decode($return_raw, true);

        if (empty($return))
            return false;

        $stream = $return['result']['server_streams'][0];

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $stream['stream_audio_info']['bitrate'];
        $np['meta']['format'] = $stream['stream_mime'];

        $u_list = (int)$stream['stream_listeners_unique'];
        $t_list = (int)$stream['stream_listeners'];
        $np['listeners'] = array(
            'current'       => $this->getListenerCount($u_list, $t_list),
            'unique'        => $u_list,
            'total'         => $t_list,
        );

        $np['current_song'] = array(
            'artist'        => $stream['stream_nowplaying']['artist'],
            'title'         => $stream['stream_nowplaying']['song'],
            'text'          => $stream['stream_nowplaying']['text'],
        );

        return true;
    }
}