<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class EverfreeRadio extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process($np)
    {
        $return_raw = $this->getUrl(array(
            'method' => 'POST',
            'params' => 'payload%5Baction%5D=radio-info',
            'referer' => 'http://everfree.net/',
            'headers' => array(
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding: gzip, deflate',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Pragma: no-cache',
                'Referer: http://everfree.net/',
                'X-Requested-With: XMLHttpRequest',
            ),
        ));

        if (!$return_raw)
            return false;

        $return = @json_decode($return_raw, true);

        if ($return['data'])
        {
            $np['listeners'] = (int)$return['data']['listeners'];

            $np['text'] = $return['data']['title'];
            list($artist, $title) = explode(" - ", $np['text'], 2);

            $np['title'] = $title;
            $np['artist'] = $artist;

            return $np;
        }

        return false;
    }
}