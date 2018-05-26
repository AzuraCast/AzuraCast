<?php
namespace AzuraCast\Radio\Frontend;

use App\Utilities;
use Doctrine\ORM\EntityManager;
use Entity;

class SHOUTcast extends FrontendAbstract
{
    protected $force_proxy_on_secure_pages = true;

    public function getWatchCommand()
    {
        $fe_config = (array)$this->station->getFrontendConfig();

        return $this->_getStationWatcherCommand(
            'shoutcast2',
            'http://localhost:' . $fe_config['port'] . '/statistics?json=1'
        );
    }

    /**
     * @inheritdoc
     */
    protected function _getNowPlaying(&$np, $payload = null, $include_clients = true)
    {
        $fe_config = (array)$this->station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        if (empty($payload)) {
            $np_url = 'http://'.(APP_INSIDE_DOCKER ? 'stations' : 'localhost').':' . $radio_port . '/statistics?json=1';
            $payload = $this->getUrl($np_url);

            if (empty($payload)) {
                return false;
            }
        }

        $current_data = json_decode($payload, true);

        $this->logger->debug('SHOUTcast 2 raw response.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName(), 'response' => $current_data]);

        $streams = count($current_data['streams']);
        $u_list = 0;
        $t_list = 0;

        $mount_repo = $this->em->getRepository(Entity\StationMount::class);

        /** @var Entity\StationMount $default_mount */
        $default_mount = $mount_repo->getDefaultMount($this->station);

        if (!($default_mount instanceof Entity\StationMount)) {
            $this->logger->error('Station does not have a default mount configured.', ['station' => ['id' => $this->station->getId(), 'name' => $this->station->getName()]]);
            return false;
        }

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

        if ($include_clients) {
            // Attempt to fetch detailed listener information for better unique statistics.
            $np['listeners']['clients'] = [];

            for($i = 1; $i <= $streams; $i++) {
                $listeners_url = 'http://'.(APP_INSIDE_DOCKER ? 'stations' : 'localhost').':' . $radio_port . '/admin.cgi?sid='.$i.'&mode=viewjson&page=3';
                $return_raw = $this->getUrl($listeners_url, [
                    'auth' => ['admin', $fe_config['admin_pw']],
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
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        $config = $this->_getConfig();
        $this->station->setFrontendConfigDefaults($this->_loadFromConfig($config));
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
        $this->station->setFrontendConfigDefaults($this->_loadFromConfig($config));

        $this->em->persist($this->station);
        $this->em->flush();

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
        }

        return '/bin/false';
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
        return @parse_ini_file($config_dir . '/sc_serv.conf', false, INI_SCANNER_RAW);
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
        $new_path = dirname(APP_INCLUDE_ROOT) . '/servers/shoutcast2/sc_serv';

        if (APP_INSIDE_DOCKER || file_exists($new_path)) {
            return $new_path;
        }

        return false;
    }
}