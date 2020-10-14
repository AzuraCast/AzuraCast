<?php

namespace App\Radio\Frontend;

use App\Entity;
use App\EventDispatcher;
use App\Http\Router;
use App\Radio\AbstractAdapter;
use App\Settings;
use App\Xml\Reader;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use NowPlaying\Adapter\AdapterFactory;
use NowPlaying\Result\Result;
use PhpIP\IP;
use PhpIP\IPBlock;
use Psr\Http\Message\UriInterface;
use Supervisor\Supervisor;

abstract class AbstractFrontend extends AbstractAdapter
{
    protected AdapterFactory $adapterFactory;

    protected Client $http_client;

    protected Router $router;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Entity\Repository\StationMountRepository $stationMountRepo;

    public function __construct(
        EntityManagerInterface $em,
        Supervisor $supervisor,
        EventDispatcher $dispatcher,
        AdapterFactory $adapterFactory,
        Client $client,
        Router $router,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationMountRepository $stationMountRepo
    ) {
        parent::__construct($em, $supervisor, $dispatcher);

        $this->adapterFactory = $adapterFactory;
        $this->http_client = $client;
        $this->router = $router;

        $this->settingsRepo = $settingsRepo;
        $this->stationMountRepo = $stationMountRepo;
    }

    /**
     * Get the default mounts when resetting or initializing a station.
     *
     * @return mixed[]
     */
    public static function getDefaultMounts(): array
    {
        return [
            [
                'name' => '/radio.mp3',
                'is_default' => 1,
                'enable_autodj' => 1,
                'autodj_format' => 'mp3',
                'autodj_bitrate' => 128,
            ],
        ];
    }

    /**
     * @return bool Whether the station supports multiple mount points per station
     */
    public static function supportsMounts(): bool
    {
        return true;
    }

    /**
     * @return bool Whether the station supports enhanced listener detail (per-client records)
     */
    public static function supportsListenerDetail(): bool
    {
        return true;
    }

    /**
     * Read configuration from external service to Station object.
     *
     * @param Entity\Station $station
     */
    abstract public function read(Entity\Station $station): bool;

    /**
     * @inheritdoc
     */
    public function getProgramName(Entity\Station $station): string
    {
        return 'station_' . $station->getId() . ':station_' . $station->getId() . '_frontend';
    }

    /**
     * @param Entity\Station $station
     * @param UriInterface|null $base_url
     */
    public function getStreamUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        $default_mount = $this->stationMountRepo->getDefaultMount($station);

        return $this->getUrlForMount($station, $default_mount, $base_url);
    }

    /**
     * @param Entity\Station $station
     * @param Entity\StationMount|null $mount
     * @param UriInterface|null $base_url
     * @param bool $append_timestamp Add the "?12345" timestamp to the end of URLs for "cache-busting".
     */
    public function getUrlForMount(
        Entity\Station $station,
        Entity\StationMount $mount = null,
        UriInterface $base_url = null,
        bool $append_timestamp = true
    ): UriInterface {
        if ($mount === null) {
            return $this->getPublicUrl($station, $base_url);
        }

        if (!empty($mount->getCustomListenUrl())) {
            return new Uri($mount->getCustomListenUrl());
        }

        $public_url = $this->getPublicUrl($station, $base_url);

        $listen_url = $public_url->withPath($public_url->getPath() . $mount->getName());

        return ($append_timestamp)
            ? $listen_url->withQuery((string)time())
            : $listen_url;
    }

    public function getPublicUrl(Entity\Station $station, $base_url = null): UriInterface
    {
        $fe_config = $station->getFrontendConfig();
        $radio_port = $fe_config->getPort();

        if (!($base_url instanceof UriInterface)) {
            $base_url = $this->router->getBaseUrl();
        }

        $use_radio_proxy = $this->settingsRepo->getSetting('use_radio_proxy', 0);

        if (
            $use_radio_proxy
            || (!Settings::getInstance()->isProduction() && !Settings::getInstance()->isDocker())
            || 'https' === $base_url->getScheme()
        ) {
            // Web proxy support.
            return $base_url
                ->withPath($base_url->getPath() . '/radio/' . $radio_port);
        }

        // Remove port number and other decorations.
        return $base_url
            ->withPort($radio_port)
            ->withPath('');
    }

    /**
     * @param Entity\Station $station
     * @param UriInterface|null $base_url
     *
     * @return UriInterface[]
     */
    public function getStreamUrls(Entity\Station $station, UriInterface $base_url = null): array
    {
        $urls = [];
        foreach ($station->getMounts() as $mount) {
            $urls[] = $this->getUrlForMount($station, $mount, $base_url);
        }

        return $urls;
    }

    abstract public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface;

    public function getNowPlaying(Entity\Station $station, bool $includeClients = true): Result
    {
        return Result::blank();
    }

    /**
     * @return mixed[]|bool
     */
    protected function processCustomConfig($custom_config_raw)
    {
        $custom_config = [];

        if (strpos($custom_config_raw, '{') === 0) {
            $custom_config = @json_decode($custom_config_raw, true, 512, JSON_THROW_ON_ERROR);
        } elseif (strpos($custom_config_raw, '<') === 0) {
            $reader = new Reader();
            $custom_config = $reader->fromString('<custom_config>' . $custom_config_raw . '</custom_config>');
        }

        return $custom_config;
    }

    protected function getRadioPort(Entity\Station $station): int
    {
        return (8000 + (($station->getId() - 1) * 10));
    }

    protected function writeIpBansFile(Entity\Station $station): string
    {
        $ips = [];
        $frontendConfig = $station->getFrontendConfig();

        $bannedIps = $frontendConfig->getBannedIps();

        if (!empty($bannedIps)) {
            $ipsRaw = array_filter(array_map('trim', explode("\n", $bannedIps)));

            foreach ($ipsRaw as $ip) {
                try {
                    if (false === strpos($ip, '/')) {
                        $ipObj = IP::create($ip);
                        $ips[] = (string)$ipObj;
                    } else {
                        // Iterate through CIDR notation
                        $ipBlock = IPBlock::create($ip);
                        foreach ($ipBlock as $ipObj) {
                            $ips[] = (string)$ipObj;
                        }
                    }
                } catch (InvalidArgumentException $e) {
                    continue;
                }
            }
        }

        $configDir = $station->getRadioConfigDir();
        $bansFile = $configDir . '/ip_bans.txt';

        file_put_contents($bansFile, implode("\n", $ips));

        return $bansFile;
    }
}
