<?php
namespace PVL\NowPlayingAdapter;

use \Entity\Station;

class Stream extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        if (stristr($this->url, 'livestream') !== FALSE)
            return $this->_processLivestream($np);
        else if (stristr($this->url, 'twitch.tv') !== FALSE)
            return $this->_processTwitchTv($np);
        else
            return false;
    }

    protected function _processLivestream(&$np)
    {
        $xml = $this->getUrl($this->url, 30);

        if (empty($xml))
            return false;

        $stream_data = \DF\Export::XmlToArray($xml);

        if ($stream_data['channel']['ls:isLive'] && $stream_data['channel']['ls:isLive'] == 'true')
        {
            $np['meta']['status'] = 'online';

            $np['listeners']['current'] = (int)$stream_data['channel']['ls:currentViewerCount'];

            $np['current_song'] = array(
                'text'      => 'Stream Online',
            );

            return true;
        }
    }

    protected function _processTwitchTv(&$np)
    {
        $return_raw = $this->getUrl();

        if (empty($return_raw))
            return false;

        $return = json_decode($return_raw, true);
        $stream = $return['stream'];

        if (empty($stream))
            return false;

        $np['meta']['status'] = 'online';

        $np['listeners']['current'] = (int)$stream['viewers'];

        $np['current_song'] = array(
            'title'     => $stream['game'],
            'artist'    => 'Stream Online',
            'text'      => 'Stream Online',
        );
        return true;
    }
}