<?php
namespace PVL\VideoAdapter;

use \Entity\Video;

class BronyTV extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $return = @json_decode($return_raw, TRUE);
        $return = $return[0];

        $np['meta']['listeners'] = (int)$return['Total_Viewers'];

        if ($return['Stream_Status'] == 'Stream is offline' || $return['Stream_Status'] == 'offline - Offline')
        {
            return false;
        }
        else
        {
            $parts = explode("-", str_replace('|', '-', $return['Stream_Status']), 2);
            $parts = array_map(function($x) { return trim($x); }, (array)$parts);

            // Now Playing defaults.
            $np['meta']['status'] = 'online';
            $np['on_air']['text'] = implode(' - ', $parts);
            return true;
        }
    }
}