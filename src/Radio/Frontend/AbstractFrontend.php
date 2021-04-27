<?php

namespace App\Radio\Frontend;

use App\Entity;
use App\Environment;
use App\EventDispatcher;
use App\Http\Router;
use App\Radio\AbstractAdapter;
use App\Xml\Reader;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use NowPlaying\AdapterFactory;
use NowPlaying\Result\Result;
use PhpIP\IP;
use PhpIP\IPBlock;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Supervisor\Supervisor;

abstract class AbstractFrontend extends AbstractAdapter
{
    public function __construct(
        protected AdapterFactory $adapterFactory,
        protected Client $http_client,
        protected Router $router,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Entity\Repository\StationMountRepository $stationMountRepo,
        Environment $environment,
        EntityManagerInterface $em,
        Supervisor $supervisor,
        EventDispatcher $dispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($environment, $em, $supervisor, $dispatcher, $logger);
    }

    /**
     * @return bool Whether the station supports multiple mount points per station
     */
    public function supportsMounts(): bool
    {
        return false;
    }

    /**
     * @return bool Whether the station supports enhanced listener detail (per-client records)
     */
    public function supportsListenerDetail(): bool
    {
        return false;
    }

    /**
     * Get the default mounts when resetting or initializing a station.
     *
     * @return mixed[]
     */
    public function getDefaultMounts(): array
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
     */
    public function getUrlForMount(
        Entity\Station $station,
        Entity\StationMount $mount = null,
        UriInterface $base_url = null
    ): UriInterface {
        if ($mount === null) {
            return $this->getPublicUrl($station, $base_url);
        }

        if (!empty($mount->getCustomListenUrl())) {
            return new Uri($mount->getCustomListenUrl());
        }

        $public_url = $this->getPublicUrl($station, $base_url);

        return $public_url->withPath($public_url->getPath() . $mount->getName());
    }

    public function getPublicUrl(Entity\Station $station, $base_url = null): UriInterface
    {
        $fe_config = $station->getFrontendConfig();
        $radio_port = $fe_config->getPort();

        if (!($base_url instanceof UriInterface)) {
            $base_url = $this->router->getBaseUrl();
        }

        $settings = $this->settingsRepo->readSettings();
        $use_radio_proxy = $settings->getUseRadioProxy();

        if (
            $use_radio_proxy
            || 'https' === $base_url->getScheme()
            || (!$this->environment->isProduction() && !$this->environment->isDocker())
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
     * @param string|null $custom_config_raw
     *
     * @return mixed[]|bool
     */
    protected function processCustomConfig(?string $custom_config_raw): array|bool
    {
        $custom_config = [];

        if (str_starts_with($custom_config_raw, '{')) {
            $custom_config = @json_decode($custom_config_raw, true, 512, JSON_THROW_ON_ERROR);
        } elseif (str_starts_with($custom_config_raw, '<')) {
            $reader = new Reader();
            $custom_config = $reader->fromString('<custom_config>' . $custom_config_raw . '</custom_config>');
        }

        return $custom_config;
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
                    if (!str_contains($ip, '/')) {
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
