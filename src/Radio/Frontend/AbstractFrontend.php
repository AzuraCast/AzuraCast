<?php

declare(strict_types=1);

namespace App\Radio\Frontend;

use App\Entity;
use App\Environment;
use App\Http\Router;
use App\Radio\AbstractAdapter;
use App\Xml\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use NowPlaying\AdapterFactory;
use NowPlaying\Result\Result;
use PhpIP\IP;
use PhpIP\IPBlock;
use Psr\EventDispatcher\EventDispatcherInterface;
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
        EventDispatcherInterface $dispatcher,
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

    public function getUrlForMount(
        Entity\Station $station,
        ?Entity\StationMount $mount = null,
        ?UriInterface $base_url = null
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

    public function getPublicUrl(Entity\Station $station, ?UriInterface $base_url = null): UriInterface
    {
        $radio_port = $station->getFrontendConfig()->getPort();
        $base_url ??= $this->router->getBaseUrl();

        $use_radio_proxy = $this->settingsRepo->readSettings()->getUseRadioProxy();

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
     * @param string $custom_config_raw
     *
     * @return mixed[]|false
     */
    protected function processCustomConfig(string $custom_config_raw): array|false
    {
        try {
            if (str_starts_with($custom_config_raw, '{')) {
                return json_decode($custom_config_raw, true, 512, JSON_THROW_ON_ERROR);
            }

            if (str_starts_with($custom_config_raw, '<')) {
                $xmlConfig = (new Reader())->fromString('<custom_config>' . $custom_config_raw . '</custom_config>');
                return (false !== $xmlConfig)
                    ? (array)$xmlConfig
                    : false;
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Could not parse custom configuration.',
                [
                    'config' => $custom_config_raw,
                    'exception' => $e,
                ]
            );
        }

        return false;
    }

    protected function writeIpBansFile(Entity\Station $station): string
    {
        $ips = [];
        $bannedIps = $station->getFrontendConfig()->getBannedIps();

        if (!empty($bannedIps)) {
            foreach (array_filter(array_map('trim', explode("\n", $bannedIps))) as $ip) {
                try {
                    if (!str_contains($ip, '/')) {
                        $ipObj = IP::create($ip);
                        $ips[] = (string)$ipObj;
                    } else {
                        // Iterate through CIDR notation
                        foreach (IPBlock::create($ip) as $ipObj) {
                            $ips[] = (string)$ipObj;
                        }
                    }
                } catch (InvalidArgumentException) {
                }
            }
        }

        $configDir = $station->getRadioConfigDir();
        $bansFile = $configDir . '/ip_bans.txt';

        file_put_contents($bansFile, implode("\n", $ips));

        return $bansFile;
    }
}
