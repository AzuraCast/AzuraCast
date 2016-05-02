<?php
namespace App\NowPlayingAdapter\Video;

use DF\Export;
use PVL\Debug;

class TwitchTv extends AdapterAbstract
{
    public function canHandle()
    {
        return (stristr($this->stream_url, 'twitch.tv') !== FALSE) || (stristr($this->data_url, 'twitch.tv') !== FALSE);
    }

    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        if (empty($this->data_url))
        {
            $twitch_url = parse_url($this->stream_url, PHP_URL_PATH);
            $twitch_username = trim($twitch_url, '/');

            $this->data_url = 'https://api.twitch.tv/kraken/streams/'.$twitch_username;
        }

        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $return = json_decode($return_raw, true);
        $stream = $return['stream'];

        Debug::print_r($stream);

        if (empty($stream))
            return false;

        $np['meta']['status'] = 'online';
        $np['meta']['listeners'] = (int)$stream['viewers'];

        $np['on_air']['text'] = $stream['game'];

        if (is_array($stream['preview']))
            $np['on_air']['thumbnail'] = $stream['preview']['medium'].'?t='.time();
        else
            $np['on_air']['thumbnail'] = $stream['preview'].'?t='.time();
        return true;
    }
}