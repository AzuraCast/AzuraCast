<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity\Station;
use App\Entity\StationMount;
use App\Radio\Enums\StreamFormats;
use App\Service\Acme;
use App\Utilities;
use App\Xml\Writer;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Uri;
use NowPlaying\Result\Result;
use Psr\Http\Message\UriInterface;
use Supervisor\Exception\SupervisorException as SupervisorLibException;
use Symfony\Component\Filesystem\Path;

final class Icecast extends AbstractFrontend
{
    public const LOGLEVEL_DEBUG = 4;
    public const LOGLEVEL_INFO = 3;
    public const LOGLEVEL_WARN = 2;
    public const LOGLEVEL_ERROR = 1;

    public function reload(Station $station): void
    {
        if ($this->hasCommand($station)) {
            $programName = $this->getSupervisorFullName($station);

            try {
                $this->supervisor->signalProcess($programName, 'HUP');
                $this->logger->info(
                    'Adapter "' . self::class . '" reloaded.',
                    ['station_id' => $station->getId(), 'station_name' => $station->getName()]
                );
            } catch (SupervisorLibException $e) {
                $this->handleSupervisorException($e, $programName, $station);
            }
        }
    }

    public function getNowPlaying(Station $station, bool $includeClients = true): Result
    {
        $feConfig = $station->getFrontendConfig();
        $radioPort = $feConfig->getPort();

        $baseUrl = $this->environment->getLocalUri()
            ->withPort($radioPort);

        $npAdapter = $this->adapterFactory->getIcecastAdapter($baseUrl);

        $npAdapter->setAdminPassword($feConfig->getAdminPassword());

        $mountPromises = [];
        $defaultMountId = null;

        foreach ($station->getMounts() as $mount) {
            if ($mount->getIsDefault()) {
                $defaultMountId = $mount->getIdRequired();
            }

            $mountPromises[$mount->getIdRequired()] = $npAdapter->getNowPlayingAsync(
                $mount->getName(),
                $includeClients
            )->then(
                function (Result $result) use ($mount) {
                    if (!empty($result->clients)) {
                        foreach ($result->clients as $client) {
                            $client->mount = 'local_' . $mount->getId();
                        }
                    }

                    $mount->setListenersTotal($result->listeners->total);
                    $mount->setListenersUnique($result->listeners->unique ?? 0);
                    $this->em->persist($mount);

                    return $result;
                }
            );
        }

        $mountPromiseResults = Utils::settle($mountPromises)->wait();

        $this->em->flush();

        $defaultResult = Result::blank();
        $otherResults = [];
        foreach ($mountPromiseResults as $mountId => $result) {
            if ($mountId === $defaultMountId) {
                $defaultResult = $result['value'] ?? Result::blank();
            } else {
                $otherResults[] = $result['value'] ?? Result::blank();
            }
        }

        foreach ($otherResults as $otherResult) {
            $defaultResult = $defaultResult->merge($otherResult);
        }

        return $defaultResult;
    }

    public function getConfigurationPath(Station $station): ?string
    {
        return $station->getRadioConfigDir() . '/icecast.xml';
    }

    public function getCurrentConfiguration(Station $station): ?string
    {
        $frontendConfig = $station->getFrontendConfig();
        $configDir = $station->getRadioConfigDir();

        $settingsBaseUrl = $this->settingsRepo->readSettings()->getBaseUrlAsUri();
        $baseUrl = $settingsBaseUrl ?? new Uri('http://localhost');

        [$certPath, $certKey] = Acme::getCertificatePaths();

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
                'ssl-private-key' => $certKey,
                'ssl-certificate' => $certPath,
                // phpcs:disable Generic.Files.LineLength
                'ssl-allowed-ciphers' => 'ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS',
                // phpcs:enable
                'deny-ip' => $this->writeIpBansFile($station),
                'deny-agents' => $this->writeUserAgentBansFile($station),
                'x-forwarded-for' => '127.0.0.1',
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

        $bannedCountries = $station->getFrontendConfig()->getBannedCountries() ?? [];
        $allowedIps = $this->getIpsAsArray($station->getFrontendConfig()->getAllowedIps());
        $useListenerAuth = !empty($bannedCountries) || !empty($allowedIps);

        /** @var StationMount $mountRow */
        foreach ($station->getMounts() as $mountRow) {
            $mount = [
                '@type' => 'normal',
                'mount-name' => $mountRow->getName(),
                'charset' => 'UTF8',
                'stream-name' => $station->getName(),
                'listenurl' => $this->getUrlForMount($station, $mountRow),
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

            if (!$mountRow->getIsVisibleOnPublicPages()) {
                $mount['hidden'] = 1;
            }

            if (!empty($mountRow->getIntroPath())) {
                $introPath = $mountRow->getIntroPath();
                // The intro path is appended to webroot, so the path should be relative to it.
                $mount['intro'] = Path::makeRelative(
                    $station->getRadioConfigDir() . '/' . $introPath,
                    '/usr/local/share/icecast/web'
                );
            }

            if (!empty($mountRow->getFallbackMount())) {
                $mount['fallback-mount'] = $mountRow->getFallbackMount();
                $mount['fallback-override'] = 1;
            } elseif ($mountRow->getEnableAutodj()) {
                $autoDjFormat = $mountRow->getAutodjFormat() ?? StreamFormats::default();
                $autoDjBitrate = $mountRow->getAutodjBitrate();

                $mount['fallback-mount'] = '/fallback-[' . $autoDjBitrate . '].' . $autoDjFormat->getExtension();
                $mount['fallback-override'] = 1;
            }

            if ($mountRow->getMaxListenerDuration()) {
                $mount['max-listener-duration'] = $mountRow->getMaxListenerDuration();
            }

            $mountFrontendConfig = trim($mountRow->getFrontendConfig() ?? '');
            if (!empty($mountFrontendConfig)) {
                $mountConf = $this->processCustomConfig($mountFrontendConfig);
                if (false !== $mountConf) {
                    $mount = Utilities\Arrays::arrayMergeRecursiveDistinct($mount, $mountConf);
                }
            }

            $mountRelayUri = $mountRow->getRelayUrlAsUri();
            if (null !== $mountRelayUri) {
                $config['relay'][] = array_filter([
                    'server' => $mountRelayUri->getHost(),
                    'port' => $mountRelayUri->getPort(),
                    'mount' => $mountRelayUri->getPath(),
                    'local-mount' => $mountRow->getName(),
                ]);
            }

            if ($useListenerAuth) {
                $mountAuthenticationUrl = $this->environment->getInternalUri()
                    ->withPath('/api/internal/' . $station->getIdRequired() . '/listener-auth')
                    ->withQuery(
                        http_build_query([
                            'api_auth' => $station->getAdapterApiKey(),
                        ])
                    );

                $mount['authentication'][] = [
                    '@type' => 'url',
                    'option' => [
                        [
                            '@name' => 'listener_add',
                            '@value' => (string)$mountAuthenticationUrl,
                        ],
                        [
                            '@name' => 'auth_header',
                            '@value' => 'icecast-auth-user: 1',
                        ],
                    ],
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

        $configString = Writer::toString($config, 'icecast');

        // Strip the first line (the XML charset)
        return substr($configString, strpos($configString, "\n") + 1);
    }

    public function getCommand(Station $station): ?string
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
        $newPath = '/usr/local/bin/icecast';
        $legacyPath = '/usr/bin/icecast2';

        if ($this->environment->isDocker() || file_exists($newPath)) {
            return $newPath;
        }

        if (file_exists($legacyPath)) {
            return $legacyPath;
        }

        return null;
    }

    public function getAdminUrl(Station $station, UriInterface $baseUrl = null): UriInterface
    {
        $publicUrl = $this->getPublicUrl($station, $baseUrl);
        return $publicUrl
            ->withPath($publicUrl->getPath() . '/admin.html');
    }
}
