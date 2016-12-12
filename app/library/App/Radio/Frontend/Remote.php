<?php
namespace App\Radio\Frontend;

use App\Debug;

class Remote extends FrontendAbstract
{
    protected $supports_mounts = false;
    protected $supports_streamers = false;

    /* Process a nowplaying record. */
    protected function _getNowPlaying(&$np)
    {
        $mounts = $this->_getMounts();

        if (empty($mounts))
            return false;

        $default_mount = $mounts[0];

        if (isset($default_mount['artist']))
        {
            $np['current_song'] = array(
                'artist' => $default_mount['artist'],
                'title' => $default_mount['title'],
                'text' => $default_mount['artist'].' - '.$default_mount['title'],
            );
        }
        else
        {
            $np['current_song'] = $this->getSongFromString($default_mount['title'], ' - ');
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $default_mount['bitrate'];
        $np['meta']['format'] = $default_mount['server_type'];

        $np['listeners']['current'] = (int)$default_mount['listeners'];
        return false;
    }

    protected function _getMounts()
    {
        $settings = (array)$this->station->frontend_config;
        $remote_url = $this->getPublicUrl();

        Debug::print_r($settings);

        switch($settings['remote_type'])
        {
            case 'icecast':
                $remote_stats_url = $remote_url.'/status-json.xsl';
                $return_raw = $this->getUrl($remote_stats_url);

                if (!$return_raw)
                    return false;

                $return = @json_decode($return_raw, true);

                Debug::print_r($return);

                if (!$return || !isset($return['icestats']['source']))
                    return false;

                $sources = $return['icestats']['source'];

                if (empty($sources))
                    return false;

                if (key($sources) === 0)
                    $mounts = $sources;
                else
                    $mounts = array($sources);

                if (count($mounts) == 0)
                    return false;

                $mounts = array_filter($mounts, function($mount) {
                    return (!empty($mount['title']) || !empty($mount['artist']));
                });

                // Sort in descending order of listeners.
                usort($mounts, function($a, $b) {
                    $a_list = (int)$a['listeners'];
                    $b_list = (int)$b['listeners'];

                    if ($a_list == $b_list)
                        return 0;
                    else
                        return ($a_list > $b_list) ? -1 : 1;
                });

                return $mounts;
            break;

            case 'shoutcast1':
                $remote_stats_url = $remote_url.'/7.html';
                $return_raw = $this->getUrl($remote_stats_url);

                if (empty($return_raw))
                    return false;

                preg_match("/<body.*>(.*)<\/body>/smU", $return_raw, $return);
                $parts = explode(",", $return[1], 7);

                Debug::print_r($parts);

                return [[
                    'title'     => $parts[6],
                    'bitrate'   => $parts[5],
                    'listenurl' => $remote_url.'/;stream.nsv',
                    'server_type' => 'audio/mpeg',
                    'listeners' => $this->getListenerCount((int)$parts[4], (int)$parts[0]),
                ]];
            break;

            case 'shoutcast2':
                $remote_stats_url = $remote_url.'/stats';
                $return_raw = $this->getUrl($remote_stats_url);

                if (empty($return_raw))
                    return false;

                $current_data = \App\Export::xml_to_array($return_raw);
                $song_data = $current_data['SHOUTCASTSERVER'];

                Debug::print_r($song_data);

                return [[
                    'title'     => $song_data['SONGTITLE'],
                    'bitrate'   => $song_data['BITRATE'],
                    'listenurl' => $remote_url.$song_data['STREAMPATH'],
                    'server_type' => $song_data['CONTENT'],
                    'listeners' => $this->getListenerCount((int)$song_data['UNIQUELISTENERS'], (int)$song_data['CURRENTLISTENERS']),
                ]];
            break;
        }

        return false;
    }

    public function read()
    {}

    public function write()
    {}

    /*
     * Process Management
     */

    public function isRunning()
    {
        return true;
    }

    public function getStreamUrl()
    {
        $mounts = $this->_getMounts();
        if (empty($mounts))
            return false;

        $default_mount = $mounts[0];
        return $default_mount['listenurl'];
    }

    public function getStreamUrls()
    {
        $mounts = $this->_getMounts();
        if (empty($mounts))
            return false;

        return \Packaged\Helpers\Arrays::ipull($mounts, 'listenurl');
    }

    public function getUrlForMount($mount_name)
    {
        return $this->getPublicUrl().$mount_name;
    }

    public function getAdminUrl()
    {
        $settings = (array)$this->station->frontend_config;

        switch($settings['remote_type'])
        {
            case 'icecast':
                return $this->getPublicUrl().'/admin/';
            break;

            case 'shoutcast1':
            case 'shoutcast2':
                return $this->getPublicUrl().'/admin.cgi';
            break;
        }

        return false;
    }

    public function getPublicUrl()
    {
        $settings = (array)$this->station->frontend_config;

        $remote_url = rtrim($settings['remote_url'], '/');

        switch($settings['remote_type'])
        {
            case 'icecast':
                return str_replace('/status-json.xsl', '', $remote_url);
            break;

            case 'shoutcast1':
                return str_replace('/7.html', '', $remote_url);
            break;

            case 'shoutcast2':
                return str_replace('/stats', '', $remote_url);
            break;
        }

        return $remote_url;
    }
}