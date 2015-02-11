<?php
namespace PVL\VideoAdapter;

use DF\Export;
use PVL\Debug;

class Livestream extends AdapterAbstract
{
    public function canHandle()
    {
        return (stristr($this->stream_url, 'livestream') !== FALSE);
    }

    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $ls_url = parse_url($this->stream_url, PHP_URL_PATH);
        $ls_username = trim($ls_url, '/');

        if (empty($this->data_url))
            $this->data_url = 'http://x'.$ls_username.'x.api.channel.livestream.com/2.0/livestatus.xml';

        $xml = $this->getUrl();

        if (empty($xml))
            return false;

        $stream_data = Export::XmlToArray($xml);

        Debug::print_r($stream_data);

        if ($stream_data['channel']['ls:isLive'] && $stream_data['channel']['ls:isLive'] == 'true')
        {
            $np['meta']['status'] = 'online';
            $np['meta']['listeners'] = (int)$stream_data['channel']['ls:currentViewerCount'];

            $np['on_air']['thumbnail'] = 'http://thumbnail.api.livestream.com/thumbnail?name='.$ls_username.'&t='.time();
            $np['on_air']['text'] = 'Stream Online';

            return true;
        }
    }
}