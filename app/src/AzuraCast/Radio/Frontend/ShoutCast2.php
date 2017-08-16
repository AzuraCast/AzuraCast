<?php
namespace AzuraCast\Radio\Frontend;

use App\Debug;
use App\Utilities;
use Doctrine\ORM\EntityManager;
use Entity;

class ShoutCast2 extends FrontendAbstract
{
    protected $supports_mounts = true;

    /* Process a nowplaying record. */
    protected function _getNowPlaying(&$np)
    {
        $fe_config = (array)$this->station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        $np_url = 'http://'.(APP_INSIDE_DOCKER ? 'stations' : 'localhost').':' . $radio_port . '/statistics?json=1';
        $return_raw = $this->getUrl($np_url);

        if (empty($return_raw)) {
            return false;
        }

        $current_data = json_decode($return_raw, true);
        Debug::print_r($current_data);

        $streams = count($current_data['streams']);
        $u_list = 0;
        $t_list = 0;

        $em = $this->di['em'];
        $mount_repo = $em->getRepository(Entity\StationMount::class);

        /** @var Entity\StationMount $default_mount */
        $default_mount = $mount_repo->getDefaultMount($this->station);

        foreach($current_data['streams'] as $stream) {
            if ($stream['streampath'] === $default_mount->getName()) {
                $song_data = $stream;
            }

            $u_list += (int)$stream['uniquelisteners'];
            $t_list += (int)$stream['currentlisteners'];
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $song_data['bitrate'];
        $np['meta']['format'] = $song_data['content'];

        $np['current_song'] = $this->getSongFromString($song_data['songtitle'], '-');

        $np['listeners'] = [
            'current' => $this->getListenerCount($u_list, $t_list),
            'unique' => $u_list,
            'total' => $t_list,
        ];

        // Attempt to fetch detailed listener information for better unique statistics.
        $np['listeners']['clients'] = [];

        for($i = 1; $i <= $streams; $i++) {
            $listeners_url = 'http://'.(APP_INSIDE_DOCKER ? 'stations' : 'localhost').':' . $radio_port . '/admin.cgi?sid='.$i.'&mode=viewjson&page=3';
            $return_raw = $this->getUrl($listeners_url, [
                'basic_auth' => 'admin:'.$fe_config['admin_pw'],
            ]);

            if (!empty($return_raw)) {
                $listeners = json_decode($return_raw, true);

                foreach((array)$listeners as $listener) {
                    $np['listeners']['clients'][] = [
                        'uid' => $listener['uid'],
                        'ip' => $listener['xff'] ?: $listener['hostname'],
                        'user_agent' => $listener['useragent'],
                        'connected_seconds' => $listener['connecttime'],
                    ];
                }
            }
        }

        return true;
    }

    public function read()
    {
        $config = $this->_getConfig();

        $this->station->setFrontendConfig($this->_loadFromConfig($config));

        return true;
    }

    public function write()
    {
        $config = $this->_getDefaults();

        $frontend_config = (array)$this->station->getFrontendConfig();

        if (!empty($frontend_config['port'])) {
            $config['portbase'] = $frontend_config['port'];
        }

        if (!empty($frontend_config['source_pw'])) {
            $config['password'] = $frontend_config['source_pw'];
        }

        if (!empty($frontend_config['admin_pw'])) {
            $config['adminpassword'] = $frontend_config['admin_pw'];
        }

        if (!empty($frontend_config['max_listeners'])) {
            $config['maxuser'] = $frontend_config['max_listeners'];
        }

        if (!empty($frontend_config['custom_config'])) {
            $custom_conf = $this->_processCustomConfig($frontend_config['custom_config']);
            if (!empty($custom_conf)) {
                $config = array_merge($config, $custom_conf);
            }
        }

        $i = 0;
        foreach ($this->station->getMounts() as $mount_row) {
            /** @var Entity\StationMount $mount_row */
            $i++;
            $config['streamid_'.$i] = $i;
            $config['streampath_'.$i] = $mount_row->getName();

            if ($mount_row->getRelayUrl()) {
                $config['streamrelayurl_'.$i] = $mount_row->getRelayUrl();
            }

            if ($mount_row->getAuthhash()) {
                $config['streamauthhash_' . $i] = $mount_row->getAuthhash();
            }
        }

        // Set any unset values back to the DB config.
        $this->station->setFrontendConfig($this->_loadFromConfig($config));

        $em = $this->di['em'];
        $em->persist($this->station);
        $em->flush();

        $config_path = $this->station->getRadioConfigDir();
        $sc_path = $config_path . '/sc_serv.conf';

        $sc_file = '';
        foreach ($config as $config_key => $config_value) {
            $sc_file .= $config_key . '=' . str_replace("\n", "", $config_value) . "\n";
        }

        file_put_contents($sc_path, $sc_file);
    }

    /*
     * Process Management
     */

    public function getCommand()
    {
        if ($binary = self::getBinary()) {
            $config_path = $this->station->getRadioConfigDir();
            $sc_config = $config_path . '/sc_serv.conf';

            return $binary . ' ' . $sc_config;
        } else {
            return '/bin/false';
        }
    }

    public function getStreamUrl()
    {
        /** @var EntityManager */
        $em = $this->di->get('em');

        $mount_repo = $em->getRepository(Entity\StationMount::class);
        $default_mount = $mount_repo->getDefaultMount($this->station);

        $mount_name = ($default_mount instanceof Entity\StationMount) ? $default_mount->getName() : '/stream/1/';

        return $this->getUrlForMount($mount_name);
    }

    public function getStreamUrls()
    {
        $urls = [];
        foreach ($this->station->getMounts() as $mount) {
            $urls[] = $this->getUrlForMount($mount->getName());
        }

        return $urls;
    }

    public function getUrlForMount($mount_name)
    {
        return $this->getPublicUrl() . $mount_name . '?' . time();
    }

    public function getAdminUrl()
    {
        return $this->getPublicUrl() . '/admin.cgi';
    }

    /*
     * Configuration
     */

    protected function _getConfig()
    {
        $config_dir = $this->station->getRadioConfigDir();
        $config = @parse_ini_file($config_dir . '/sc_serv.conf', false, INI_SCANNER_RAW);

        return $config;
    }

    protected function _loadFromConfig($config)
    {
        return [
            'port' => $config['portbase'],
            'source_pw' => $config['password'],
            'admin_pw' => $config['adminpassword'],
            'max_listeners' => $config['maxuser'],
        ];
    }

    protected function _getDefaults()
    {
        $config_path = $this->station->getRadioConfigDir();

        $defaults = [
            'password' => Utilities::generatePassword(),
            'adminpassword' => Utilities::generatePassword(),
            'logfile' => $config_path . '/sc_serv.log',
            'w3clog' => $config_path . '/sc_w3c.log',
            'banfile' => $config_path . '/sc_serv.ban',
            'ripfile' => $config_path . '/sc_serv.rip',
            'maxuser' => 250,
            'portbase' => $this->_getRadioPort(),
            'requirestreamconfigs' => 1,
        ];

        return $defaults;
    }

    public static function getBinary()
    {
        $new_path = realpath(APP_INCLUDE_ROOT . '/..') . '/servers/shoutcast2/sc_serv';

        if (APP_INSIDE_DOCKER || file_exists($new_path)) {
            return $new_path;
        } else {
            return false;
        }
    }
}