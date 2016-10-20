<?php
namespace App\Radio\Frontend;

use App\Utilities;
use Entity\Station;
use Entity\Settings;

class IceCast extends FrontendAbstract
{
    /* Process a nowplaying record. */
    protected function _getNowPlaying(&$np)
    {
        $fe_config = (array)$this->station->frontend_config;
        $radio_port = $fe_config['port'];

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

        $frontend_config = [
            'port'      => $config['listen-socket']['port'],
            'source_pw' => $config['authentication']['source-password'],
            'admin_pw'  => $config['authentication']['admin-password'],
        ];

        $this->station->frontend_config = $frontend_config;
        return true;
    }

    public function write()
    {
        $config = $this->_getDefaults();

        $frontend_config = (array)$this->station->frontend_config;

        // Set up streamer support.
        $url = $this->di['url'];

        $config['mount'][0]['authentication'] = array(
            '@type' => 'url',
            'option' => [
                [
                    '@name' => 'stream_auth',
                    '@value' => $url->route(['module' => 'api', 'controller' => 'internal', 'action' => 'streamauth', 'id' => $this->station->id], true)
                ],
            ],
        );

        if (!empty($frontend_config['port']))
            $config['listen-socket']['port'] = $frontend_config['port'];

        if (!empty($frontend_config['source_pw']))
            $config['authentication']['source-password'] = $frontend_config['source_pw'];

        if (!empty($frontend_config['admin_pw']))
            $config['authentication']['admin-password'] = $frontend_config['admin_pw'];

        if (!empty($frontend_config['streamer_pw']))
            $config['mount'][0]['password'] = $frontend_config['streamer_pw'];

        // Set any unset values back to the DB config.
        $frontend_config['port'] = $config['listen-socket']['port'];
        $frontend_config['source_pw'] = $config['authentication']['source-password'];
        $frontend_config['admin_pw'] = $config['authentication']['admin-password'];
        $frontend_config['streamer_pw'] = $config['mount'][0]['password'];

        $this->station->frontend_config = $frontend_config;

        $em = $this->di['em'];
        $em->persist($this->station);
        $em->flush();

        $config_path = $this->station->getRadioConfigDir();
        $icecast_path = $config_path.'/icecast.xml';

        $writer = new \App\Xml\Writer;
        $icecast_config_str = $writer->toString($config, 'icecast');

        // Strip the first line (the XML charset)
        $icecast_config_str = substr( $icecast_config_str, strpos($icecast_config_str, "\n")+1 );

        file_put_contents($icecast_path, $icecast_config_str);
    }

    /*
     * Process Management
     */

    public function isRunning()
    {
        return $this->_isPidRunning($this->station->getRadioConfigDir().'/icecast.pid');
    }

    public function stop()
    {
        $this->_killPid($this->station->getRadioConfigDir().'/icecast.pid');
    }

    public function start()
    {
        $config_path = $this->station->getRadioConfigDir();
        $icecast_config = $config_path.'/icecast.xml';

        if ($this->isRunning())
        {
            $this->log(_('Not starting, process is already running.'));
            return;
        }

        $cmd = \App\Utilities::run_command('icecast2 -b -c '.$icecast_config.' 2>&1');

        if (!empty($cmd['output']))
            $this->log($cmd['output']);

        if (!empty($cmd['error']))
            $this->log($cmd['error'], 'red');
    }

    public function restart()
    {
        $this->stop();
        $this->start();
    }

    public function getStreamUrl()
    {
        return $this->getPublicUrl().'/radio.mp3?played='.time();
    }

    public function getAdminUrl()
    {
        return $this->getPublicUrl().'/admin/';
    }

    public function getPublicUrl()
    {
        $fe_config = (array)$this->station->frontend_config;
        $radio_port = $fe_config['port'];

        $base_url = $this->di['em']->getRepository('Entity\Settings')->getSetting('base_url', 'localhost');

        // Vagrant port-forwarding mode.
        if (APP_APPLICATION_ENV == 'development')
            return 'http://'.$base_url.':8080/radio/'.$radio_port;
        else
            return 'http://'.$base_url.':'.$radio_port;
    }

    /*
     * Configuration
     */

    protected function _getConfig()
    {
        $config_path = $this->station->getRadioConfigDir();
        $icecast_path = $config_path.'/icecast.xml';

        $defaults = $this->_getDefaults();

        if (file_exists($icecast_path))
        {
            $reader = new \App\Xml\Reader;
            $data = $reader->fromFile($icecast_path);

            return Utilities::array_merge_recursive_distinct($defaults, $data);
        }

        return $defaults;
    }

    protected function _getDefaults()
    {
        $config_dir = $this->station->getRadioConfigDir();

        return [
            'location' => 'AzuraCast',
            'admin' => 'icemaster@localhost',
            'hostname' => $this->di['em']->getRepository('Entity\Settings')->getSetting('base_url', 'localhost'),
            'limits' => [
                'clients' => 100,
                'sources' => 3,
                'threadpool' => 5,
                'queue-size' => 524288,
                'client-timeout' => 30,
                'header-timeout' => 15,
                'source-timeout' => 10,
                'burst-on-connect' => 1,
                'burst-size' => 65535,
            ],
            'authentication' => [
                'source-password' => Utilities::generatePassword(),
                'relay-password' => Utilities::generatePassword(),
                'admin-user' => 'admin',
                'admin-password' => Utilities::generatePassword(),
            ],

            'listen-socket' => [
                'port' => $this->_getRadioPort(),
                'shoutcast-mount' => '/radio.mp3',
            ],

            'mount' => [
                [
                    '@type'     => 'normal',
                    'mount-name' => '/radio.mp3',
                    'fallback-mount' => '/autodj.mp3',
                    'fallback-override' => 1,

                    'username' => 'shoutcast',
                    'password' => Utilities::generatePassword(),
                ],
                [
                    '@type'     => 'normal',
                    'mount-name' => '/autodj.mp3',
                    'fallback-mount' => '/error.mp3',
                    'fallback-override' => 1,
                ],
            ],
            'fileserve' => 1,
            'paths' => [
                'basedir' => '/usr/share/icecast2',
                'logdir' => $config_dir,
                'webroot' => '/usr/share/icecast2/web',
                'adminroot' => '/usr/share/icecast2/admin',
                'pidfile' => $config_dir.'/icecast.pid',
                'alias' => [
                    '@source' => '/',
                    '@destination' => '/status.xsl',
                ],
            ],
            'logging' => [
                'accesslog' => 'icecast_access.log',
                'errorlog' => 'icecast_error.log',
                'loglevel' => 3,
                'logsize' => 10000,
            ],
            'security' => [
                'chroot' => 0,
            ],
        ];
    }

    protected function _getRadioPort()
    {
        return (8000 + (($this->station->id - 1) * 10));
    }
}