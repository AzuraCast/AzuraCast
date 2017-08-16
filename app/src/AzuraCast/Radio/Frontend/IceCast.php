<?php
namespace AzuraCast\Radio\Frontend;

use App\Debug;
use App\Utilities;
use Doctrine\ORM\EntityManager;
use Entity\StationMount;
use Entity;

class IceCast extends FrontendAbstract
{
    /* Process a nowplaying record. */
    protected function _getNowPlaying(&$np)
    {
        $fe_config = (array)$this->station->getFrontendConfig();
        $reader = new \App\Xml\Reader();

        $radio_port = $fe_config['port'];
        $np_url = 'http://'.(APP_INSIDE_DOCKER ? 'stations' : 'localhost').':' . $radio_port . '/admin/stats';

        Debug::log($np_url);

        $return_raw = $this->getUrl($np_url, [
            'basic_auth' => 'admin:'.$fe_config['admin_pw'],
        ]);

        if (!$return_raw) {
            return false;
        }

        $return = $reader->fromString($return_raw);
        Debug::print_r($return);

        if (!$return || empty($return['source'])) {
            return false;
        }

        $sources = $return['source'];
        $mounts = (key($sources) === 0) ? $sources : [$sources];

        $em = $this->di['em'];
        $mount_repo = $em->getRepository(Entity\StationMount::class);
        $default_mount = $mount_repo->getDefaultMount($this->station);

        $current_listeners = 0;
        $unique_listeners = [];
        $clients = [];

        $np['listeners']['clients'] = [];

        $song_data_by_mount = [];

        foreach($mounts as $mount) {
            $song_data_by_mount[$mount['@mount']] = $mount;

            $current_listeners += $mount['listeners'];

            // Attempt to fetch detailed listener information for better unique statistics.
            $listeners_url = 'http://'.(APP_INSIDE_DOCKER ? 'stations' : 'localhost').':' . $radio_port . '/admin/listclients?mount='.urlencode($mount['@mount']);
            $return_raw = $this->getUrl($listeners_url, [
                'basic_auth' => 'admin:'.$fe_config['admin_pw'],
            ]);

            if (!empty($return_raw)) {
                $listeners_raw = $reader->fromString($return_raw);

                if (!empty($listeners_raw['source']['listener']))
                {
                    $listeners = $listeners_raw['source']['listener'];
                    $listeners = (key($listeners) === 0) ? $listeners : [$listeners];

                    foreach($listeners as $listener) {
                        $client = [
                            'uid' => $listener['ID'],
                            'ip' => $listener['IP'],
                            'user_agent' => $listener['UserAgent'],
                            'connected_seconds' => $listener['Connected'],
                        ];

                        $client_hash = Entity\Listener::calculateListenerHash($client);
                        $unique_listeners[$client_hash] = $client_hash;
                        $clients[] = $client;
                    }
                }
            }
        }

        $unique_listeners = count($unique_listeners);

        $np['listeners'] = [
            'current' => $this->getListenerCount($unique_listeners, $current_listeners),
            'unique' => $unique_listeners,
            'total' => $current_listeners,
            'clients' => $clients,
        ];

        // Check the default mount, then its fallback if otherwise not available.
        if (!empty($song_data_by_mount[$default_mount->name]['title'])) {
            $song_data = $song_data_by_mount[$default_mount->name];
        } elseif (!empty($song_data_by_mount[$default_mount->fallback_mount]['title'])) {
            $song_data = $song_data_by_mount[$default_mount->fallback_mount];
        } else {
            return false;
        }

        if (isset($song_data['artist'])) {
            $np['current_song'] = [
                'artist' => $song_data['artist'],
                'title' => $song_data['title'],
                'text' => $song_data['artist'] . ' - ' . $song_data['title'],
            ];
        } else {
            $np['current_song'] = $this->getSongFromString($song_data['title'], ' - ');
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $song_data['bitrate'];
        $np['meta']['format'] = $song_data['server_type'];

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
            $config['listen-socket']['port'] = $frontend_config['port'];
        }

        if (!empty($frontend_config['source_pw'])) {
            $config['authentication']['source-password'] = $frontend_config['source_pw'];
        }

        if (!empty($frontend_config['admin_pw'])) {
            $config['authentication']['admin-password'] = $frontend_config['admin_pw'];
        }

        if (!empty($frontend_config['streamer_pw'])) {
            foreach ($config['mount'] as &$mount) {
                if (!empty($mount['password'])) {
                    $mount['password'] = $frontend_config['streamer_pw'];
                }
            }
        }

        if (!empty($frontend_config['max_listeners'])) {
            $config['limits']['clients'] = $frontend_config['max_listeners'];
        }

        if (!empty($frontend_config['custom_config'])) {
            $custom_conf = $this->_processCustomConfig($frontend_config['custom_config']);
            if (!empty($custom_conf)) {
                $config = \App\Utilities::array_merge_recursive_distinct($config, $custom_conf);
            }
        }

        // Set any unset values back to the DB config.
        $this->station->setFrontendConfig($this->_loadFromConfig($config));

        $em = $this->di['em'];
        $em->persist($this->station);
        $em->flush();

        $config_path = $this->station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $writer = new \App\Xml\Writer;
        $icecast_config_str = $writer->toString($config, 'icecast');

        // Strip the first line (the XML charset)
        $icecast_config_str = substr($icecast_config_str, strpos($icecast_config_str, "\n") + 1);

        file_put_contents($icecast_path, $icecast_config_str);
    }

    /*
     * Process Management
     */

    public function getCommand()
    {
        if ($binary = self::getBinary()) {
            $config_path = $this->station->getRadioConfigDir() . '/icecast.xml';
            return $binary . ' -c ' . $config_path;
        } else {
            return '/bin/false';
        }
    }

    public function getStreamUrl()
    {
        /** @var EntityManager */
        $em = $this->di->get('em');

        $mount_repo = $em->getRepository(StationMount::class);
        $default_mount = $mount_repo->getDefaultMount($this->station);

        $mount_name = ($default_mount instanceof StationMount) ? $default_mount->getName() : '/radio.mp3';

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
        return $this->getPublicUrl() . '/admin/';
    }

    /*
     * Configuration
     */

    protected function _getConfig()
    {
        $config_path = $this->station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $defaults = $this->_getDefaults();

        if (file_exists($icecast_path)) {
            $reader = new \App\Xml\Reader;
            $data = $reader->fromFile($icecast_path);

            return Utilities::array_merge_recursive_distinct($defaults, $data);
        }

        return $defaults;
    }

    protected function _loadFromConfig($config)
    {
        $frontend_config = (array)$this->station->getFrontendConfig();

        return [
            'custom_config' => $frontend_config['custom_config'],
            'port' => $config['listen-socket']['port'],
            'source_pw' => $config['authentication']['source-password'],
            'admin_pw' => $config['authentication']['admin-password'],
            'streamer_pw' => $config['mount'][0]['password'],
            'max_listeners' => $config['limits']['clients'],
        ];
    }

    protected function _getDefaults()
    {
        $config_dir = $this->station->getRadioConfigDir();

        $defaults = [
            'location' => 'AzuraCast',
            'admin' => 'icemaster@localhost',
            'hostname' => $this->di['em']->getRepository('Entity\Settings')->getSetting('base_url', 'localhost'),
            'limits' => [
                'clients' => 250,
                'sources' => 3,
                // 'threadpool' => 5,
                'queue-size' => 524288,
                'client-timeout' => 30,
                'header-timeout' => 15,
                'source-timeout' => 10,
                // 'burst-on-connect' => 1,
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
            ],

            'mount' => [],
            'fileserve' => 1,
            'paths' => [
                'basedir' => '/usr/local/share/icecast',
                'logdir' => $config_dir,
                'webroot' => '/usr/local/share/icecast/web',
                'adminroot' => '/usr/local/share/icecast/admin',
                'pidfile' => $config_dir . '/icecast.pid',
                'x-forwarded-for' => '127.0.0.1',
                'alias' => [
                    '@source' => '/',
                    '@dest' => '/status.xsl',
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

        foreach ($this->station->getMounts() as $mount_row) {
            /** @var Entity\StationMount $mount_row */

            $mount = [
                '@type' => 'normal',
                'mount-name' => $mount_row->getName(),
            ];

            if (!empty($mount_row->getFallbackMount())) {
                $mount['fallback-mount'] = $mount_row->getFallbackMount();
                $mount['fallback-override'] = 1;
            }

            if ($mount_row->getFrontendConfig()) {
                $mount_conf = $this->_processCustomConfig($mount_row->getFrontendConfig());
                if (!empty($mount_conf)) {
                    $mount = \App\Utilities::array_merge_recursive_distinct($mount, $mount_conf);
                }
            }

            if ($mount_row->getRelayUrl()) {
                $relay_parts = parse_url($mount_row->getRelayUrl());

                $defaults['relay'][] = [
                    'server' => $relay_parts['host'],
                    'port' => $relay_parts['port'],
                    'mount' => $relay_parts['path'],
                    'local-mount' => $mount_row->getName(),
                ];
            }

            $defaults['mount'][] = $mount;
        }

        return $defaults;
    }


    public static function getBinary()
    {
        $new_path = '/usr/local/bin/icecast';
        $legacy_path = '/usr/bin/icecast2';

        if (APP_INSIDE_DOCKER || file_exists($new_path)) {
            return $new_path;
        } elseif (file_exists($legacy_path)) {
            return $legacy_path;
        } else {
            return false;
        }
    }
}