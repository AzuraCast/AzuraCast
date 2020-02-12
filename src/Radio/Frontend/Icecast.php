<?php
namespace App\Radio\Frontend;

use App\Entity;
use App\Logger;
use App\Settings;
use App\Utilities;
use App\Xml\Reader;
use App\Xml\Writer;
use NowPlaying\Adapter\AdapterAbstract;
use NowPlaying\Exception;
use Psr\Http\Message\UriInterface;

class Icecast extends AbstractFrontend
{
    public const LOGLEVEL_DEBUG = 4;
    public const LOGLEVEL_INFO = 3;
    public const LOGLEVEL_WARN = 2;
    public const LOGLEVEL_ERROR = 1;

    public function getNowPlaying(Entity\Station $station, $payload = null, $include_clients = true): array
    {
        $fe_config = (array)$station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        $base_url = 'http://' . (Settings::getInstance()->isDocker() ? 'stations' : 'localhost') . ':' . $radio_port;

        $np_adapter = new \NowPlaying\Adapter\Icecast($base_url, $this->http_client);
        $np_adapter->setAdminPassword($fe_config['admin_pw']);

        $np_final = AdapterAbstract::NOWPLAYING_EMPTY;
        $np_final['listeners']['clients'] = [];

        try {
            foreach ($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $np_final = $this->_processNowPlayingForMount(
                    $mount,
                    $np_final,
                    $np_adapter->getNowPlaying($mount->getName()),
                    $include_clients ? $np_adapter->getClients($mount->getName(), true) : null
                );
            }
        } catch (Exception $e) {
            Logger::getInstance()->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }

        return $np_final;
    }

    public function read(Entity\Station $station): bool
    {
        $config = $this->_getConfig($station);
        $station->setFrontendConfigDefaults($this->_loadFromConfig($station, $config));
        return true;
    }

    protected function _getConfig(Entity\Station $station)
    {
        $config_path = $station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $defaults = $this->_getDefaults($station);

        if (file_exists($icecast_path)) {
            $reader = new Reader;
            $data = $reader->fromFile($icecast_path);

            return self::arrayMergeRecursiveDistinct($defaults, $data);
        }

        return $defaults;
    }

    /*
     * Process Management
     */

    protected function _getDefaults(Entity\Station $station)
    {
        $config_dir = $station->getRadioConfigDir();

        $defaults = [
            'location' => 'AzuraCast',
            'admin' => 'icemaster@localhost',
            'hostname' => $this->settingsRepo->getSetting(Entity\Settings::BASE_URL, 'localhost'),
            'limits' => [
                'clients' => 2500,
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
                'errorlog' => '/dev/stderr',
                'loglevel' => Settings::getInstance()->isProduction() ? self::LOGLEVEL_WARN : self::LOGLEVEL_INFO,
                'logsize' => 10000,
            ],
            'security' => [
                'chroot' => 0,
            ],
        ];

        // Allow all sources to set the X-Forwarded-For header
        $settings = Settings::getInstance();

        if ($settings->isDocker() && $settings[Settings::DOCKER_REVISION] >= 3) {
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
                'genre' => $station->getGenre(),
            ];

            if (!empty($mount_row->getFallbackMount())) {
                $mount['fallback-mount'] = $mount_row->getFallbackMount();
                $mount['fallback-override'] = 1;
            }

            if ($mount_row->getFrontendConfig()) {

                $mount_conf = $this->_processCustomConfig($mount_row->getFrontendConfig());

                if (!empty($mount_conf)) {
                    $mount = self::arrayMergeRecursiveDistinct($mount, $mount_conf);
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

    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /*
     * Configuration
     */

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
                $config = self::arrayMergeRecursiveDistinct($config, $custom_conf);
            }
        }

        // Set any unset values back to the DB config.
        $station->setFrontendConfigDefaults($this->_loadFromConfig($station, $config));

        $this->em->persist($station);
        $this->em->flush();

        $config_path = $station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $writer = new Writer;
        $icecast_config_str = $writer->toString($config, 'icecast');

        // Strip the first line (the XML charset)
        $icecast_config_str = substr($icecast_config_str, strpos($icecast_config_str, "\n") + 1);

        file_put_contents($icecast_path, $icecast_config_str);
        return true;
    }

    public function getCommand(Entity\Station $station): ?string
    {
        if ($binary = self::getBinary()) {
            $config_path = $station->getRadioConfigDir() . '/icecast.xml';
            return $binary . ' -c ' . $config_path;
        }
        return '/bin/false';
    }

    public static function getBinary()
    {
        $new_path = '/usr/local/bin/icecast';
        $legacy_path = '/usr/bin/icecast2';

        if (Settings::getInstance()->isDocker() || file_exists($new_path)) {
            return $new_path;
        }

        if (file_exists($legacy_path)) {
            return $legacy_path;
        } else {
            return false;
        }
    }

    public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        $public_url = $this->getPublicUrl($station, $base_url);
        return $public_url
            ->withPath($public_url->getPath() . '/admin.html');
    }
}
