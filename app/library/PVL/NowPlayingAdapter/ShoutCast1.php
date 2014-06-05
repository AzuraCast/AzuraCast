<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class ShoutCast1 extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process($np)
    {
        $return_raw = $this->getUrl();

        if ($return_raw)
        {
            preg_match("/<body.*>(.*)<\/body>/smU", $return_raw, $return);
            $parts = explode(",", $return[1], 7);

            list($artist, $title) = explode(" - ", $parts[6], 2);

            $np['listeners_unique'] = (int)$parts[4];
            $np['listeners_total'] = (int)$parts[0];
            $np['listeners'] = $this->getListenerCount($np['listeners_unique'], $np['listeners_total']);

            $np['title'] = $title;
            $np['artist'] = $artist;
            $np['text'] = $parts[6];
            return $np;
        }

        return false;
    }
}