<?php
namespace PVL\VideoAdapter;

use PVL\Debug;

class StreamUp extends AdapterAbstract
{
    public function canHandle()
    {
        return (stristr($this->stream_url, 'streamup.com') !== FALSE) || (stristr($this->data_url, 'streamup.com') !== FALSE);
    }

    /* Process a nowplaying record. */
    protected function _process(&$np)
    {
        $slug_url = parse_url($this->stream_url, PHP_URL_PATH);
        $slug_username = trim($slug_url, '/');
        $stream = $this->getStreamInfo($slug_username);

        if (!$stream)
            return false;

        Debug::print_r($stream);

        $np['meta']['status'] = 'online';
        $np['meta']['listeners'] = (int)$stream['live_viewers_count'];

        $np['on_air']['text'] = $stream['stream_title'];
        $np['on_air']['thumbnail'] = $stream['snapshot']['small'];
        return true;
    }

    public function getStreamInfo($channel_slug)
    {
        $stream_data = \DF\Cache::get('streamup_stream_data');

        if (!$stream_data)
        {
            $stream_data = $this->_getStreamData('http://api.streamup.com/1.0/channels');
            \DF\Cache::set($stream_data, 'streamup_stream_data', array(), 60);
        }

        if (isset($stream_data[$channel_slug]))
            return $stream_data[$channel_slug];
        else
            return false;
    }

    public function _getStreamData($url)
    {
        $streams = array();

        $page_result_raw = file_get_contents($url);
        if ($page_result_raw)
        {
            $page_result = json_decode($page_result_raw, true);

            foreach((array)$page_result['channels'] as $channel)
                $streams[$channel['user']['username']] = $channel;

            if (isset($page_result['next_page']))
            {
                $next_page = $this->_getStreamData($page_result['next_page']);
                $streams = array_merge($streams, $next_page);
            }
        }

        return $streams;
    }
}