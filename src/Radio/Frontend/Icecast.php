<?php

namespace App\Radio\Frontend;

use App\Entity;
use App\Logger;
use App\Radio\CertificateLocator;
use App\Settings;
use App\Utilities;
use App\Xml\Reader;
use App\Xml\Writer;
use Exception;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Adapter\AdapterFactory;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;

class Icecast extends AbstractFrontend
{
    public const LOGLEVEL_DEBUG = 4;
    public const LOGLEVEL_INFO = 3;
    public const LOGLEVEL_WARN = 2;
    public const LOGLEVEL_ERROR = 1;

    public function getNowPlaying(Entity\Station $station, bool $includeClients = true): Result
    {
        $feConfig = $station->getFrontendConfig();
        $radioPort = $feConfig->getPort();

        $baseUrl = 'http://' . (Settings::getInstance()->isDocker() ? 'stations' : 'localhost') . ':' . $radioPort;

        $npAdapter = $this->adapterFactory->getAdapter(
            AdapterFactory::ADAPTER_ICECAST,
            $baseUrl
        );

        $npAdapter->setAdminPassword($feConfig->getAdminPassword());

        $defaultResult = Result::blank();
        $otherResults = [];

        try {
            foreach ($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $result = $npAdapter->getNowPlaying($mount->getName(), $includeClients);

                $mount->setListenersTotal($result->listeners->total);
                $mount->setListenersUnique($result->listeners->unique);
                $this->em->persist($mount);

                if ($mount->getIsDefault()) {
                    $defaultResult = $result;
                } else {
                    $otherResults[] = $result;
                }
            }

            $this->em->flush();

            foreach ($otherResults as $otherResult) {
                $defaultResult = $defaultResult->merge($otherResult);
            }
        } catch (Exception $e) {
            Logger::getInstance()->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }

        return $defaultResult;
    }

    public function read(Entity\Station $station): bool
    {
        $config = $this->getConfig($station);
        $station->setFrontendConfigDefaults($this->loadFromConfig($station, $config));
        return true;
    }

    /**
     * @return mixed[]
     */
    protected function getConfig(Entity\Station $station): array
    {
        $config_path = $station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $defaults = $this->getDefaults($station);

        if (file_exists($icecast_path)) {
            $reader = new Reader();
            $data = $reader->fromFile($icecast_path);

            return self::arrayMergeRecursiveDistinct($defaults, $data);
        }

        return $defaults;
    }

    /*
     * Process Management
     */

    /**
     * @return mixed[]
     */
    protected function getDefaults(Entity\Station $station): array
    {
        $config_dir = $station->getRadioConfigDir();
        $settings = Settings::getInstance();

        $settingsBaseUrl = $this->settingsRepo->getSetting(Entity\Settings::BASE_URL, 'http://localhost');
        if (strpos($settingsBaseUrl, 'http') !== 0) {
            $settingsBaseUrl = 'http://' . $settingsBaseUrl;
        }
        $baseUrl = new Uri($settingsBaseUrl);

        $certPaths = CertificateLocator::findCertificate();

        $defaults = [
            'location' => 'AzuraCast',
            'admin' => 'icemaster@localhost',
            'hostname' => $baseUrl->getHost(),
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
                'port' => $this->getRadioPort($station),
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
                'ssl-private-key' => $certPaths->getKeyPath(),
                'ssl-certificate' => $certPaths->getCertPath(),
                // phpcs:disable Generic.Files.LineLength
                'ssl-allowed-ciphers' => 'ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS',
                // phpcs:enable
                'deny-ip' => $this->writeIpBansFile($station),
                'x-forwarded-for' => $settings->isDocker() ? '172.*.*.*' : '127.0.0.1',
            ],
            'logging' => [
                'accesslog' => 'icecast_access.log',
                'errorlog' => '/dev/stderr',
                'loglevel' => $settings->isProduction() ? self::LOGLEVEL_WARN : self::LOGLEVEL_INFO,
                'logsize' => 10000,
            ],
            'security' => [
                'chroot' => 0,
            ],
        ];

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
                $mount_conf = $this->processCustomConfig($mount_row->getFrontendConfig());

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
     * @return mixed[]
     *
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
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

    /**
     * @return mixed[]
     */
    protected function loadFromConfig(Entity\Station $station, $config): array
    {
        $frontend_config = $station->getFrontendConfig();

        return [
            Entity\StationFrontendConfiguration::CUSTOM_CONFIGURATION => $frontend_config->getCustomConfiguration(),
            Entity\StationFrontendConfiguration::SOURCE_PASSWORD => $config['authentication']['source-password'],
            Entity\StationFrontendConfiguration::ADMIN_PASSWORD => $config['authentication']['admin-password'],
            Entity\StationFrontendConfiguration::RELAY_PASSWORD => $config['authentication']['relay-password'],
            Entity\StationFrontendConfiguration::STREAMER_PASSWORD => $config['mount'][0]['password'],
            Entity\StationFrontendConfiguration::MAX_LISTENERS => $config['limits']['clients'],
        ];
    }

    public function write(Entity\Station $station): bool
    {
        $config = $this->getDefaults($station);

        $frontend_config = $station->getFrontendConfig();

        $port = $frontend_config->getPort();
        if (null !== $port) {
            $config['listen-socket']['port'] = $port;
        }

        $sourcePw = $frontend_config->getSourcePassword();
        if (!empty($sourcePw)) {
            $config['authentication']['source-password'] = $sourcePw;
        }

        $adminPw = $frontend_config->getAdminPassword();
        if (!empty($adminPw)) {
            $config['authentication']['admin-password'] = $adminPw;
        }

        $relayPw = $frontend_config->getRelayPassword();
        if (!empty($relayPw)) {
            $config['authentication']['relay-password'] = $relayPw;
        }

        $streamerPw = $frontend_config->getStreamerPassword();
        if (!empty($streamerPw)) {
            foreach ($config['mount'] as &$mount) {
                if (!empty($mount['password'])) {
                    $mount['password'] = $streamerPw;
                }
            }
        }

        $maxListeners = $frontend_config->getMaxListeners();
        if (null !== $maxListeners) {
            $config['limits']['clients'] = $maxListeners;
        }

        $customConfig = $frontend_config->getCustomConfiguration();
        if (!empty($customConfig)) {
            $custom_conf = $this->processCustomConfig($customConfig);
            if (!empty($custom_conf)) {
                $config = self::arrayMergeRecursiveDistinct($config, $custom_conf);
            }
        }

        // Set any unset values back to the DB config.
        $station->setFrontendConfigDefaults($this->loadFromConfig($station, $config));

        $this->em->persist($station);
        $this->em->flush();

        $config_path = $station->getRadioConfigDir();
        $icecast_path = $config_path . '/icecast.xml';

        $writer = new Writer();
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

    /**
     * @inheritDoc
     */
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
