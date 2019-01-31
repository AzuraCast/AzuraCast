<?php
namespace App\Radio\Frontend;

use Azura\EventDispatcher;
use App\Http\Router;
use App\Entity;
use Doctrine\ORM\EntityManager;
use fXmlRpc\Exception\FaultException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;
use Psr\Http\Message\UriInterface;
use Supervisor\Supervisor;

abstract class AbstractFrontend extends \App\Radio\AbstractAdapter
{
    /** @var Client */
    protected $http_client;

    /** @var Router */
    protected $router;

    public function __construct(EntityManager $em, Supervisor $supervisor, Logger $logger, EventDispatcher $dispatcher, Client $client, Router $router)
    {
        parent::__construct($em, $supervisor, $logger, $dispatcher);

        $this->http_client = $client;
        $this->router = $router;
    }

    /**
     * Read configuration from external service to Station object.
     *
     * @param Entity\Station $station
     * @return bool
     */
    abstract public function read(Entity\Station $station): bool;

    /**
     * @param Entity\Station $station
     * @return null|string The command to pass the station-watcher app.
     */
    public function getWatchCommand(Entity\Station $station): ?string
    {
        return null;
    }

    /**
     * @param Entity\Station $station
     * @return bool Whether a station-watcher command exists for this adapter.
     */
    public function hasWatchCommand(Entity\Station $station): bool
    {
        if (APP_TESTING_MODE || !APP_INSIDE_DOCKER || !$station->isEnabled()) {
            return false;
        }

        return ($this->getCommand($station) !== null);
    }

    /**
     * Return the supervisord programmatic name for the station-watcher command.
     *
     * @param Entity\Station $station
     * @return string
     */
    public function getWatchProgramName(Entity\Station $station): string
    {
        return 'station_' . $station->getId() . ':station_' . $station->getId() . '_watcher';
    }

    /**
     * Get the AzuraCast station-watcher binary command for the specified adapter and watch URI.
     *
     * @param Entity\Station $station
     * @param string $adapter
     * @param string $watch_uri
     * @return string
     */
    protected function _getStationWatcherCommand(Entity\Station $station, $adapter, $watch_uri): string
    {
        if (APP_INSIDE_DOCKER) {
            $base_url = (APP_DOCKER_REVISION >= 5) ? 'http://web' : 'http://nginx';
        } else {
            $base_url = 'http://localhost';
        }

        $notify_uri = $base_url.'/api/internal/'.$station->getId().'/notify?api_auth='.$station->getAdapterApiKey();

        return '/var/azuracast/servers/station-watcher/station-watcher '.$adapter.' '.$watch_uri.' '.$notify_uri.' '.$station->getShortName();
    }

    public function getProgramName(Entity\Station $station): string
    {
        return 'station_' . $station->getId() . ':station_' . $station->getId() . '_frontend';
    }

    /**
     * @param Entity\Station $station
     * @param UriInterface|null $base_url
     * @return UriInterface
     */
    public function getStreamUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface
    {
        /** @var Entity\Repository\StationMountRepository $mount_repo */
        $mount_repo = $this->em->getRepository(Entity\StationMount::class);
        $default_mount = $mount_repo->getDefaultMount($station);

        return $this->getUrlForMount($station, $default_mount, $base_url);
    }

    /**
     * @param Entity\Station $station
     * @param UriInterface|null $base_url
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

    /**
     * @param Entity\Station $station
     * @param Entity\StationMount|null $mount
     * @param UriInterface|null $base_url
     * @return UriInterface
     */
    public function getUrlForMount(Entity\Station $station, Entity\StationMount $mount = null, UriInterface $base_url = null): UriInterface
    {
        if ($mount === null) {
            return $this->getPublicUrl($station, $base_url);
        }

        if (!empty($mount->getCustomListenUrl())) {
            return new Uri($mount->getCustomListenUrl());
        }

        $public_url = $this->getPublicUrl($station, $base_url);
        return $public_url
            ->withPath($public_url->getPath().$mount->getName())
            ->withQuery((string)time());
    }

    abstract public function getAdminUrl(Entity\Station $station, UriInterface $base_url = null): UriInterface;

    public function getPublicUrl(Entity\Station $station, $base_url = null): UriInterface
    {
        $fe_config = (array)$station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        if (!($base_url instanceof UriInterface)) {
            $base_url = $this->router->getBaseUrl();
        }

        $use_radio_proxy = $settings_repo->getSetting('use_radio_proxy', 0);

        if ( $use_radio_proxy
            || (!APP_IN_PRODUCTION && !APP_INSIDE_DOCKER)
            || 'https' === $base_url->getScheme()) {
            // Web proxy support.
            return $base_url
                ->withPath($base_url->getPath().'/radio/' . $radio_port);
        } else {
            // Remove port number and other decorations.
            return $base_url
                ->withPort($radio_port)
                ->withPath('');
        }
    }

    /**
     * @param Entity\Station $station
     * @param string|null $payload A prepopulated payload (to avoid duplicate web requests)
     * @param bool $include_clients Whether to try to retrieve detailed listener client info
     * @return array Whether the NP update succeeded
     */
    public function getNowPlaying(Entity\Station $station, $payload = null, $include_clients = true): array
    {
        return \NowPlaying\Adapter\AdapterAbstract::NOWPLAYING_EMPTY;
    }

    /**
     * @inheritdoc
     */
    public function stop(Entity\Station $station): void
    {
        parent::stop($station);

        if ($this->hasWatchCommand($station)) {
            $program_name = $this->getWatchProgramName($station);

            try {
                $this->supervisor->stopProcess($program_name);
                $this->logger->info('Frontend watcher stopped.', ['station_id' => $station->getId(), 'station_name' => $station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name, $station);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function start(Entity\Station $station): void
    {
        parent::start($station);

        if ($this->hasWatchCommand($station)) {
            $program_name = $this->getWatchProgramName($station);

            try {
                $this->supervisor->startProcess($program_name);
                $this->logger->info('Frontend watcher started.', ['station_id' => $station->getId(), 'station_name' => $station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name, $station);
            }
        }
    }

    protected function _processCustomConfig($custom_config_raw)
    {
        $custom_config = [];

        if (substr($custom_config_raw, 0, 1) == '{') {
            $custom_config = @json_decode($custom_config_raw, true);
        } elseif (substr($custom_config_raw, 0, 1) == '<') {
            $reader = new \App\Xml\Reader;
            $custom_config = $reader->fromString('<custom_config>' . $custom_config_raw . '</custom_config>');
        }

        return $custom_config;
    }

    protected function _getRadioPort(Entity\Station $station)
    {
        return (8000 + (($station->getId() - 1) * 10));
    }

    /**
     * Get the default mounts when resetting or initializing a station.
     *
     * @return array
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
            ]
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
}
