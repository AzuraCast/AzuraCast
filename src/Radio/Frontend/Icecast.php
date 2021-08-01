<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity;
use App\Radio\CertificateLocator;
use App\Utilities;
use App\Xml\Writer;
use Exception;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;

class Icecast extends AbstractFrontend
{
    public const LOGLEVEL_DEBUG = 4;
    public const LOGLEVEL_INFO = 3;
    public const LOGLEVEL_WARN = 2;
    public const LOGLEVEL_ERROR = 1;

    public function supportsMounts(): bool
    {
        return true;
    }

    public function supportsListenerDetail(): bool
    {
        return true;
    }

    public function getNowPlaying(Entity\Station $station, bool $includeClients = true): Result
    {
        $feConfig = $station->getFrontendConfig();
        $radioPort = $feConfig->getPort();

        /** @noinspection HttpUrlsUsage */
        $baseUrl = 'http://' . ($this->environment->isDocker() ? 'stations' : 'localhost') . ':' . $radioPort;

        $npAdapter = $this->adapterFactory->getIcecastAdapter($baseUrl);

        $npAdapter->setAdminPassword($feConfig->getAdminPassword());

        $defaultResult = Result::blank();
        $otherResults = [];

        foreach ($station->getMounts() as $mount) {
            try {
                $result = $npAdapter->getNowPlaying($mount->getName(), $includeClients);

                if (!empty($result->clients)) {
                    foreach ($result->clients as $client) {
                        $client->mount = 'local_' . $mount->getId();
                    }
                }
            } catch (Exception $e) {
                $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));

                $result = Result::blank();
            }

            $mount->setListenersTotal($result->listeners->total);
            $mount->setListenersUnique($result->listeners->unique ?? 0);
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

        return $defaultResult;
    }

    public function getConfigurationPath(Entity\Station $station): ?string
    {
        return $station->getRadioConfigDir() . '/icecast.xml';
    }

    public function getCurrentConfiguration(Entity\Station $station): ?string
    {
        $frontendConfig = $station->getFrontendConfig();
        $configDir = $station->getRadioConfigDir();

        $settings = $this->settingsRepo->readSettings();

        $settingsBaseUrl = $settings->getBaseUrl() ?: 'http://localhost';
        if (!str_starts_with($settingsBaseUrl, 'http')) {
            /** @noinspection HttpUrlsUsage */
            $settingsBaseUrl = 'http://' . $settingsBaseUrl;
        }
        $baseUrl = new Uri($settingsBaseUrl);

        $certPaths = CertificateLocator::findCertificate();

        $xForwardedFor = $this->environment->isDocker()
            ? ['172.*.*.*', '192.*.*.*']
            : '127.0.0.1';

        $config = [
            'location' => 'AzuraCast',
            'admin' => 'icemaster@localhost',
            'hostname' => $baseUrl->getHost(),
            'limits' => [
                'clients' => $frontendConfig->getMaxListeners() ?? 2500,
                'sources' => $station->getMounts()->count(),
                'queue-size' => 524288,
                'client-timeout' => 30,
                'header-timeout' => 15,
                'source-timeout' => 10,
                'burst-size' => 65535,
            ],
            'authentication' => [
                'source-password' => $frontendConfig->getSourcePassword(),
                'relay-password' => $frontendConfig->getRelayPassword(),
                'admin-user' => 'admin',
                'admin-password' => $frontendConfig->getAdminPassword(),
            ],

            'listen-socket' => [
                'port' => $frontendConfig->getPort(),
            ],

            'mount' => [],
            'fileserve' => 1,
            'paths' => [
                'basedir' => '/usr/local/share/icecast',
                'logdir' => $configDir,
                'webroot' => '/usr/local/share/icecast/web',
                'adminroot' => '/usr/local/share/icecast/admin',
                'pidfile' => $configDir . '/icecast.pid',
                'alias' => [
                    [
                        '@source' => '/',
                        '@dest' => '/status.xsl',
                    ],
                ],
                'ssl-private-key' => $certPaths->getKeyPath(),
                'ssl-certificate' => $certPaths->getCertPath(),
                // phpcs:disable Generic.Files.LineLength
                'ssl-allowed-ciphers' => 'ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS',
                // phpcs:enable
                'deny-ip' => $this->writeIpBansFile($station),
                'x-forwarded-for' => $xForwardedFor,
            ],
            'logging' => [
                'accesslog' => 'icecast_access.log',
                'errorlog' => '/dev/stderr',
                'loglevel' => $this->environment->isProduction() ? self::LOGLEVEL_WARN : self::LOGLEVEL_INFO,
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
            ];

            if (!empty($station->getDescription())) {
                $mount['stream-description'] = $station->getDescription();
            }

            if (!empty($station->getUrl())) {
                $mount['stream-url'] = $station->getUrl();
            }

            if (!empty($station->getGenre())) {
                $mount['genre'] = $station->getGenre();
            }

            if (!$mount_row->isVisibleOnPublicPages()) {
                $mount['hidden'] = 1;
            }

            if (!empty($mount_row->getIntroPath())) {
                $introPath = $mount_row->getIntroPath();
                // The intro path is appended to webroot, hence the 5 ../es. Amazingly, this works!
                $mount['intro'] = '../../../../../' . $station->getRadioConfigDir() . '/' . $introPath;
            }

            if (!empty($mount_row->getFallbackMount())) {
                $mount['fallback-mount'] = $mount_row->getFallbackMount();
                $mount['fallback-override'] = 1;
            }

            if ($mount_row->getMaxListenerDuration()) {
                $mount['max-listener-duration'] = $mount_row->getMaxListenerDuration();
            }

            $mountFrontendConfig = trim($mount_row->getFrontendConfig() ?? '');
            if (!empty($mountFrontendConfig)) {
                $mount_conf = $this->processCustomConfig($mountFrontendConfig);
                if (false !== $mount_conf) {
                    $mount = Utilities\Arrays::arrayMergeRecursiveDistinct($mount, $mount_conf);
                }
            }

            $mountRelayUrl = $mount_row->getRelayUrl();
            if (!empty($mountRelayUrl)) {
                $mountRelayUri = new Uri($mountRelayUrl);

                $config['relay'][] = [
                    'server' => $mountRelayUri->getHost(),
                    'port' => $mountRelayUri->getPort(),
                    'mount' => $mountRelayUri->getPath(),
                    'local-mount' => $mount_row->getName(),
                ];
            }

            $config['mount'][] = $mount;
        }

        $customConfig = trim($frontendConfig->getCustomConfiguration() ?? '');
        if (!empty($customConfig)) {
            $customConfParsed = $this->processCustomConfig($customConfig);

            if (false !== $customConfParsed) {
                // Special handling for aliases.
                if (isset($customConfParsed['paths']['alias'])) {
                    $alias = (array)$customConfParsed['paths']['alias'];
                    if (!is_numeric(key($alias))) {
                        $alias = [$alias];
                    }
                    $customConfParsed['paths']['alias'] = $alias;
                }

                $config = Utilities\Arrays::arrayMergeRecursiveDistinct($config, $customConfParsed);
            }
        }

        $configString = (new Writer())->toString($config, 'icecast');

        // Strip the first line (the XML charset)
        return substr($configString, strpos($configString, "\n") + 1);
    }

    public function getCommand(Entity\Station $station): ?string
    {
        if ($binary = $this->getBinary()) {
            return $binary . ' -c ' . $this->getConfigurationPath($station);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getBinary(): ?string
    {
        $new_path = '/usr/local/bin/icecast';
        $legacy_path = '/usr/bin/icecast2';

        if ($this->environment->isDocker() || file_exists($new_path)) {
            return $new_path;
        }

        if (file_exists($legacy_path)) {
            return $legacy_path;
        }

        return null;
    }

    public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        $public_url = $this->getPublicUrl($station, $base_url);
        return $public_url
            ->withPath($public_url->getPath() . '/admin.html');
    }
}
