<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class ShoutCast2 extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $current_data = \DF\Export::XmlToArray($return_raw);
        $song_data = $current_data['SHOUTCASTSERVER'];

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $song_data['BITRATE'];
        $np['meta']['format'] = $song_data['CONTENT'];

        $title_parts = explode('-', str_replace('   ', ' - ', $song_data['SONGTITLE']), 2);
        $artist = trim(array_shift($title_parts));
        $title = trim(implode('-', $title_parts));

        $np['current_song'] = array(
            'title'     => $title,
            'artist'    => $artist,
            'text'      => $song_data['SONGTITLE'],
        );

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