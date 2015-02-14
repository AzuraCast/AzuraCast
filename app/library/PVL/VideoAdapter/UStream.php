<?php
namespace PVL\VideoAdapter;

use DF\Export;
use PVL\Debug;

class UStream extends AdapterAbstract
{
    public function canHandle()
    {
        return (stristr($this->stream_url, 'ustream.tv') !== FALSE);
    }

    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        if (empty($this->data_url))
        {
            $us_url = parse_url($this->stream_url, PHP_URL_PATH);
            $us_path_parts = explode('/', trim($us_url, '/'));

            $us_username = array_pop($us_path_parts);

            $this->data_url = 'https://api.ustream.tv/channels/'.$us_username.'.json';
        }

        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $return = json_decode($return_raw, true);
        $channel = $return['channel'];

        Debug::print_r($channel);

        if (empty($channel))
            return false;

        $np['meta']['status'] = ($channel['status'] == 'live') ? 'online' : 'offline';
        $np['meta']['listeners'] = (isset($channel['stats']['viewer'])) ? (int)$channel['stats']['viewer'] : 0;

        $np['on_air']['text'] = $channel['title'];
        $np['on_air']['thumbnail'] = $channel['thumbnail']['live'];
        return true;
    }
}