<?php
namespace App\Radio\Frontend;

use App\Utilities;
use Doctrine\ORM\EntityManager;
use App\Entity;
use NowPlaying\Exception;
use Psr\Http\Message\UriInterface;

class Icecast extends FrontendAbstract
{
    public const LOGLEVEL_DEBUG = 4;
    public const LOGLEVEL_INFO = 3;
    public const LOGLEVEL_WARN = 2;
    public const LOGLEVEL_ERROR = 1;

    public function getWatchCommand(Entity\Station $station): ?string
    {
        $fe_config = (array)$station->getFrontendConfig();

        return $this->_getStationWatcherCommand(
            $station,
            'icecast',
            'http://admin:'.$fe_config['admin_pw'].'@localhost:' . $fe_config['port'] . '/admin/stats'
        );
    }

    public function getNowPlaying(Entity\Station $station, $payload = null, $include_clients = true): array
    {
        $fe_config = (array)$station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        $base_url = 'http://' . (APP_INSIDE_DOCKER ? 'stations' : 'localhost') . ':' . $radio_port;

        $np_adapter = new \NowPlaying\Adapter\Icecast($base_url, $this->http_client);
        $np_adapter->setAdminPassword($fe_config['admin_pw']);

        $np_final = \NowPlaying\Adapter\AdapterAbstract::NOWPLAYING_EMPTY;
        $np_final['listeners']['clients'] = [];

        try {
            foreach($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $np = $np_adapter->getNowPlaying($mount->getName());

                if ($include_clients) {
                    $np['listeners']['clients'] = $np_adapter->getClients($mount->getName(), true);
                    $np['listeners']['unique'] = count($np['listeners']['clients']);
                } else {
                    $np['listeners']['clients'] = [];
                }

                if ($mount->getIsDefault()) {
                    $np_final['current_song'] = $np['current_song'];
                    $np_final['meta'] = $np['meta'];
                }

                $np_final['listeners']['clients'] = array_merge($np_final['listeners']['clients'], $np['listeners']['clients']);

                $np_final['listeners']['current'] += $np['listeners']['current'];
                $np_final['listeners']['unique'] += $np['listeners']['unique'];
                $np_final['listeners']['total'] += $np['listeners']['total'];

                $this->logger->debug('Response for mount point', ['mount' => $mount->getName(), 'response' => $np]);
            }

            $this->logger->debug('Aggregated NowPlaying response', ['response' => $np_final]);
        } catch(Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }

        return $np_final;
    }

    public function read(Entity\Station $station): bool
    {
        $config = $this->_getConfig($station);
        $station->setFrontendConfigDefaults($this->_loadFromConfig($station, $config));
        return true;
    }

    public function write(Entity\Station $station): bool
    {
        $config = $this->_getDefaults($station);

        $frontend_config = (array)$station->getFrontendConfig();

        if (!empty($frontend_config['port'])) {
            $config['listen-socket']['port'] = $frontend_config['port'];
        }

        if (!empty($frontend_config['source_pw'])) {
            $config['authentication']['source-password'] = $frontend_config['source_pw'];
        }

        if (!empty($frontend_config['admin_pw'])) {
            $config['authentication']['admin-password'] = $frontend_config['admin_pw'];
        }

        if (!empty($frontend_config['relay_pw'])) {
            $config['authentication']['relay-password'] = $frontend_config['relay_pw'];
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
                $config = Utilities::array_merge_recursive_distinct($config, $custom_conf);
            }
        }

        // Set any unset values back to the DB config.
        $station->setFrontendConfigDefaults($this->_loadFromConfig($station, $config));

        $this->em->persist($station);
        $this->em->flush();

        $config_path = $station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $writer = new \App\Xml\Writer;
        $icecast_config_str = $writer->toString($config, 'icecast');

        // Strip the first line (the XML charset)
        $icecast_config_str = substr($icecast_config_str, strpos($icecast_config_str, "\n") + 1);

        file_put_contents($icecast_path, $icecast_config_str);
        return true;
    }

    /*
     * Process Management
     */

    public function getCommand(Entity\Station $station): ?string
    {
        if ($binary = self::getBinary()) {
            $config_path = $station->getRadioConfigDir() . '/icecast.xml';
            return $binary . ' -c ' . $config_path;
        }
        return '/bin/false';
    }

    public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        $public_url = $this->getPublicUrl($station, $base_url);
        return $public_url
            ->withPath($public_url->getPath().'/admin.html');
    }

    /*
     * Configuration
     */

    protected function _getConfig(Entity\Station $station)
    {
        $config_path = $station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $defaults = $this->_getDefaults($station);

        if (file_exists($icecast_path)) {
            $reader = new \App\Xml\Reader;
            $data = $reader->fromFile($icecast_path);

            return Utilities::array_merge_recursive_distinct($defaults, $data);
        }

        return $defaults;
    }

    protected function _loadFromConfig(Entity\Station $station, $config)
    {
        $frontend_config = (array)$station->getFrontendConfig();

        return [
            'custom_config' => $frontend_config['custom_config'],
            'source_pw' => $config['authentication']['source-password'],
            'admin_pw' => $config['authentication']['admin-password'],
            'relay_pw' => $config['authentication']['relay-password'],
            'streamer_pw' => $config['mount'][0]['password'],
            'max_listeners' => $config['limits']['clients'],
        ];
    }

    protected function _getDefaults(Entity\Station $station)
    {
        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $config_dir = $station->getRadioConfigDir();

        $defaults = [
            'location' => 'AzuraCast',
            'admin' => 'icemaster@localhost',
            'hostname' => $settings_repo->getSetting('base_url', 'localhost'),
            'limits' => [
                'clients' => 250,
                'sources' => $station->getMounts()->count(),
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
                'port' => $this->_getRadioPort($station),
            ],

            'mount' => [],
            'fileserve' => 1,
            'paths' => [
                'basedir' => '/usr/local/share/icecast',
                'logdir' => $config_dir,
                'webroot' => '/usr/local/share/icecast/web',
                'adminroot' => '/usr/local/share/icecast/admin',
                'pidfile' => $config_dir . '/icecast.pid',
                'alias' => [
                    '@source' => '/',
                    '@dest' => '/status.xsl',
                ],
                'ssl-private-key' => '/etc/nginx/ssl/ssl.key',
                'ssl-certificate' => '/etc/nginx/ssl/ssl.crt',
                'ssl-allowed-ciphers' => 'ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS',
                'x-forwarded-for' => '127.0.0.1',
            ],
            'logging' => [
                'accesslog' => 'icecast_access.log',
                'errorlog' => 'icecast_error.log',
                'loglevel' => (APP_IN_PRODUCTION) ? self::LOGLEVEL_WARN : self::LOGLEVEL_INFO,
                'logsize' => 10000,
            ],
            'security' => [
                'chroot' => 0,
            ],
        ];

        // Allow all sources to set the X-Forwarded-For header
        if (APP_INSIDE_DOCKER && APP_DOCKER_REVISION >= 3) {
            $defaults['paths']['all-x-forwarded-for'] = '1';
        }

        foreach ($station->getMounts() as $mount_row) {
            /** @var Entity\StationMount $mount_row */

            $mount = [
                '@type' => 'normal',
                'mount-name' => $mount_row->getName(),
                'charset' => 'UTF8',

                'stream-name' => $station->getName(),
                'stream-description' => $station->getDescription(),
                'stream-url' => $station->getUrl(),
                'stream-genre' => $station->getGenre(),
            ];

            if (!empty($mount_row->getFallbackMount())) {
                $mount['fallback-mount'] = $mount_row->getFallbackMount();
                $mount['fallback-override'] = 1;
            }

            if ($mount_row->getFrontendConfig()) {

                $mount_conf = $this->_processCustomConfig($mount_row->getFrontendConfig());

                if (!empty($mount_conf)) {
                    $mount = Utilities::array_merge_recursive_distinct($mount, $mount_conf);
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
