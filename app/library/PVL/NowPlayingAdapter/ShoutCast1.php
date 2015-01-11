<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class ShoutCast1 extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        preg_match("/<body.*>(.*)<\/body>/smU", $return_raw, $return);
        $parts = explode(",", $return[1], 7);

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $parts[5];
        $np['meta']['format'] = 'audio/mpeg';

        $np['current_song'] = $this->getSongFromString($parts[6], ' - ');

        $u_list = (int)$parts[4];
        $t_list = (int)$parts[0];
        $np['listeners'] = array(
            'current'       => $this->getListenerCount($u_list, $t_list),
            'unique'        => $u_list,
            'total'         => $t_list,
        );

        return true;
    }
}