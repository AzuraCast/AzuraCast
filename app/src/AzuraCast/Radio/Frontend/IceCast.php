<?php
namespace AzuraCast\Radio\Frontend;

use App\Debug;
use App\Utilities;
use Doctrine\ORM\EntityManager;
use Entity\StationMount;

class IceCast extends FrontendAbstract
{
    /* Process a nowplaying record. */
    protected function _getNowPlaying(&$np)
    {
        $fe_config = (array)$this->station->frontend_config;
        $reader = new \App\Xml\Reader();

        $radio_port = $fe_config['port'];
        $np_url = 'http://localhost:' . $radio_port . '/admin/stats';

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

        if (key($sources) === 0) {
            $mounts = $sources;
        } else {
            $mounts = [$sources];
        }

        $mounts = array_filter($mounts, function ($mount) {
            return (!empty($mount['title']) || !empty($mount['artist']));
        });

        // Sort in descending order of listeners.
        usort($mounts, function ($a, $b) {
            $a_list = (int)$a['listeners'];
            $b_list = (int)$b['listeners'];

            if ($a_list == $b_list) {
                return 0;
            } else {
                return ($a_list > $b_list) ? -1 : 1;
            }
        });

        $temp_array = $mounts[0];

        if (isset($temp_array['artist'])) {
            $np['current_song'] = [
                'artist' => $temp_array['artist'],
                'title' => $temp_array['title'],
                'text' => $temp_array['artist'] . ' - ' . $temp_array['title'],
            ];
        } else {
            $np['current_song'] = $this->getSongFromString($temp_array['title'], ' - ');
        }

        $np['meta']['status'] = 'online';
        $np['meta']['bitrate'] = $temp_array['bitrate'];
        $np['meta']['format'] = $temp_array['server_type'];

        $np['listeners']['current'] = (int)$temp_array['listeners'];

        if (!empty($temp_array['@mount'])) {
            // Attempt to fetch detailed listener information for better unique statistics.
            $selected_mount = $temp_array['@mount'];

            $listeners_url = 'http://localhost:' . $radio_port . '/admin/listclients?mount='.urlencode($selected_mount);

            $return_raw = $this->getUrl($listeners_url, [
                'basic_auth' => 'admin:'.$fe_config['admin_pw'],
            ]);

            if (!empty($return_raw)) {
                $listeners_raw = $reader->fromString($return_raw);

                $np['listeners']['clients'] = [];

                if (!empty($listeners_raw['source']['listener']))
                {
                    if (key($listeners_raw['source']['listener']) === 0) {
                        $listeners = $listeners_raw['source']['listener'];
                    } else {
                        $listeners = [$listeners_raw['source']['listener']];
                    }

                    foreach($listeners as $listener) {
                        $np['listeners']['clients'][] = [
                            'uid' => $listener['ID'],
                            'ip' => $listener['IP'],
                            'user_agent' => $listener['UserAgent'],
                            'connected_seconds' => $listener['Connected'],
                        ];
                    }
                }
            }
        }

        return true;
    }

    public function read()
    {
        $config = $this->_getConfig();

        $this->station->frontend_config = $this->_loadFromConfig($config);

        return true;
    }

    public function write()
    {
        $config = $this->_getDefaults();

        $frontend_config = (array)$this->station->frontend_config;

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

        if (!empty($frontend_config['custom_config'])) {
            $custom_conf = $this->_processCustomConfig($frontend_config['custom_config']);
            if (!empty($custom_conf)) {
                $config = \App\Utilities::array_merge_recursive_distinct($config, $custom_conf);
            }
        }

        // Set any unset values back to the DB config.
        $this->station->frontend_config = $this->_loadFromConfig($config);

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
        $config_path = $this->station->getRadioConfigDir() . '/icecast.xml';

        return '/usr/local/bin/icecast -c ' . $config_path;
    }

    public function getStreamUrl()
    {
        /** @var EntityManager */
        $em = $this->di->get('em');

        $mount_repo = $em->getRepository(StationMount::class);
        $default_mount = $mount_repo->getDefaultMount($this->station);

        $mount_name = ($default_mount instanceof StationMount) ? $default_mount->name : '/radio.mp3';

        return $this->getUrlForMount($mount_name);
    }

    public function getStreamUrls()
    {
        $urls = [];
        foreach ($this->station->mounts as $mount) {
            $urls[] = $this->getUrlForMount($mount->name);
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
        $frontend_config = (array)$this->station->frontend_config;

        return [
            'custom_config' => $frontend_config['custom_config'],
            'port' => $config['listen-socket']['port'],
            'source_pw' => $config['authentication']['source-password'],
            'admin_pw' => $config['authentication']['admin-password'],
            'streamer_pw' => $config['mount'][0]['password'],
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
                'clients' => 100,
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

        foreach ($this->station->mounts as $mount_row) {
            $mount = [
                '@type' => 'normal',
                'mount-name' => $mount_row->name,
            ];

            if (!empty($mount_row->fallback_mount)) {
                $mount['fallback-mount'] = $mount_row->fallback_mount;
                $mount['fallback-override'] = 1;
            }

            if ($mount_row->frontend_config) {
                $mount_conf = $this->_processCustomConfig($mount_row->frontend_config);
                if (!empty($mount_conf)) {
                    $mount = \App\Utilities::array_merge_recursive_distinct($mount, $mount_conf);
                }
            }

            if ($mount_row->relay_url) {
                $relay_parts = parse_url($mount_row->relay_url);

                $defaults['relay'][] = [
                    'server' => $relay_parts['host'],
                    'port' => $relay_parts['port'],
                    'mount' => $relay_parts['path'],
                    'local-mount' => $mount_row->name,
                ];
            }

            $defaults['mount'][] = $mount;
        }

        return $defaults;
    }
}