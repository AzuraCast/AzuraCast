<?php
namespace App\Radio\Frontend;

use Entity\Station;

class ShoutCast2 extends AdapterAbstract
{
    /* TODO: This class not fully implemented! */

    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $current_data = \App\Export::xml_to_array($return_raw);
        $song_data = $current_data['SHOUTCASTSERVER'];

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $song_data['BITRATE'];
        $np['meta']['format'] = $song_data['CONTENT'];

        $np['current_song'] = $this->getSongFromString($song_data['SONGTITLE'], '-');

        $u_list = (int)$song_data['UNIQUELISTENERS'];
        $t_list = (int)$song_data['CURRENTLISTENERS'];
        $np['listeners'] = array(
            'current'       => $this->getListenerCount($u_list, $t_list),
            'unique'        => $u_list,
            'total'         => $t_list,
        );

        return true;
    }
}