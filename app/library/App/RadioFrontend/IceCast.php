<?php
namespace App\RadioFrontend;

use Entity\Station;

class IceCast extends AdapterAbstract
{
    /* Process a nowplaying record. */
    protected function _getNowPlaying(&$np)
    {
        $radio_port = $this->station->radio_port;

        $np_url = 'http://localhost:'.$radio_port.'/status-json.xsl';

        \App\Debug::log($np_url);

        $return_raw = $this->getUrl($np_url);

        if (!$return_raw)
            return false;

        $return = @json_decode($return_raw, true);

        \App\Debug::print_r($return);

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

        // Sort in descending order of listeners.
        usort($mounts, function($a, $b) {
            $a_list = (int)$a['listeners'];
            $b_list = (int)$b['listeners'];

            if ($a_list == $b_list)
                return 0;
            else
                return ($a_list > $b_list) ? -1 : 1;
        });

        $temp_array = $mounts[0];

        if (isset($temp_array['artist']))
        {
            $np['current_song'] = array(
                'artist' => $temp_array['artist'],
                'title' => $temp_array['title'],
                'text' => $temp_array['artist'].' - '.$temp_array['title'],
            );
        }
        else
        {
            $np['current_song'] = $this->getSongFromString($temp_array['title'], ' - ');
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $temp_array['bitrate'];
        $np['meta']['format'] = $temp_array['server_type'];

        $np['listeners']['current'] = (int)$temp_array['listeners'];

        return true;
    }

    public function read()
    {
        $config = $this->_getConfig();

        $this->station->radio_port = $config['listen-socket']['port'];
        $this->station->radio_source_pw = $config['authentication']['source-password'];
        $this->station->radio_admin_pw = $config['authentication']['admin-password'];

        return true;
    }

    public function write()
    {
        /* TODO: Implement config writing */
    }

    public function restart()
    {
        return exec('sudo service icecast2 restart');
    }

    protected function _getConfig()
    {
        $config_path = '/etc/icecast2/icecast.xml';

        $reader = new \Zend\Config\Reader\Xml();
        $data = $reader->fromFile($config_path);
        return $data;
    }
}