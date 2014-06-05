<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class CentovaCast extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process($np)
    {
        $return_raw = $this->getUrl();

        if ($return_raw)
        {
            $return = @json_decode($return_raw, TRUE);

            if ($return)
            {
                list($artist, $track) = explode(' - ', $return['SERVERTITLE'], 2);

                $np['listeners_unique'] = (int)$song_data['UNIQUELISTENERS'];
                $np['listeners_total'] = (int)$song_data['CURRENTLISTENERS'];
                $np['listeners'] = self::getListenerCount($np['listeners_unique'], $np['listeners_total']);

                $np['artist'] = $artist;
                $np['title'] = $track;
                $np['text'] = $return['SERVERTITLE'];
                return $np;
            }
        }

        return false;
    }
}