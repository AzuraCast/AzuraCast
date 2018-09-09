<?php
namespace App\Radio\Frontend;

use App\Http\Router;
use App\Entity;
use App\Radio\Traits\AdapterCommon;
use Doctrine\ORM\EntityManager;
use fXmlRpc\Exception\FaultException;
use GuzzleHttp\Client;
use Monolog\Logger;
use Supervisor\Supervisor;

abstract class FrontendAbstract extends \App\Radio\AdapterAbstract
{
    use AdapterCommon;

    /** @var Router */
    protected $router;

    public function __construct(EntityManager $em, Supervisor $supervisor, Logger $logger, Client $client, Router $router)
    {
        parent::__construct($em, $supervisor, $logger);

        $this->http_client = $client;
        $this->router = $router;
    }

    /**
     * @return bool Whether the station supports multiple mount points per station
     */
    public function supportsMounts(): bool
    {
        return true;
    }

    /**
     * @return bool Whether the station supports enhanced listener detail (per-client records)
     */
    public function supportsListenerDetail(): bool
    {
        return true;
    }

    /**
     * Read configuration from external service to Station object.
     * @return bool
     */
    abstract public function read(): bool;

    /**
     * @return null|string The command to pass the station-watcher app.
     */
    public function getWatchCommand(): ?string
    {
        return null;
    }

    /**
     * @return bool Whether a station-watcher command exists for this adapter.
     */
    public function hasWatchCommand(): bool
    {
        if (APP_TESTING_MODE || !APP_INSIDE_DOCKER || !$this->station->isEnabled()) {
            return false;
        }

        return ($this->getCommand() !== null);
    }

    /**
     * Return the supervisord programmatic name for the station-watcher command.
     *
     * @return string
     */
    public function getWatchProgramName(): string
    {
        return 'station_' . $this->station->getId() . ':station_' . $this->station->getId() . '_watcher';
    }

    /**
     * Get the AzuraCast station-watcher binary command for the specified adapter and watch URI.
     *
     * @param $adapter
     * @param $watch_uri
     * @return string
     */
    protected function _getStationWatcherCommand($adapter, $watch_uri): string
    {
        $base_url = (APP_INSIDE_DOCKER) ? 'http://nginx' : 'http://localhost';
        $notify_uri = $base_url.'/api/internal/'.$this->station->getId().'/notify?api_auth='.$this->station->getAdapterApiKey();

        return '/var/azuracast/servers/station-watcher/station-watcher '.$adapter.' '.$watch_uri.' '.$notify_uri.' '.$this->station->getShortName();
    }

    /**
     * Get the default mounts when resetting or initializing a station.
     *
     * @return array
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
            ]
        ];
    }

    public function getProgramName(): string
    {
        return 'station_' . $this->station->getId() . ':station_' . $this->station->getId() . '_frontend';
    }

    public function getStreamUrl(): string
    {
        $mount_repo = $this->em->getRepository(Entity\StationMount::class);
        $default_mount = $mount_repo->getDefaultMount($this->station);

        return $this->getUrlForMount($default_mount);
    }

    public function getStreamUrls(): array
    {
        $urls = [];
        foreach ($this->station->getMounts() as $mount) {
            $urls[] = $this->getUrlForMount($mount);
        }

        return $urls;
    }

    public function getUrlForMount($mount)
    {
        if(!$mount instanceof Entity\StationMount) return null;
        return (!empty($mount->getCustomListenUrl())
            ? $mount->getCustomListenUrl()
            : $this->getPublicUrl() . $mount->getName()
        ) . '?' . time();
    }

    abstract public function getAdminUrl(): string;

    public function getPublicUrl(): string
    {
        $fe_config = (array)$this->station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $base_url = $this->router->getBaseUrl();
        $use_radio_proxy = $settings_repo->getSetting('use_radio_proxy', 0);

        if ( $use_radio_proxy
            || (!APP_IN_PRODUCTION && !APP_INSIDE_DOCKER)
            || APP_IS_SECURE) {
            // Web proxy support.
            return $base_url . '/radio/' . $radio_port;
        } else {
            // Remove port number and other decorations.
            return parse_url($base_url, \PHP_URL_SCHEME).'://'.parse_url($base_url, \PHP_URL_HOST) . ':' . $radio_port;
        }
    }

    /**
     * @param $np
     * @param string|null $payload A prepopulated payload (to avoid duplicate web requests)
     * @param bool $include_clients Whether to try to retrieve detailed listener client info
     * @return bool Whether the NP update succeeded
     */
    public function updateNowPlaying(&$np, $payload = null, $include_clients = true): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        parent::stop();

        if ($this->hasWatchCommand()) {
            $program_name = $this->getWatchProgramName();

            try {
                $this->supervisor->stopProcess($program_name);
                $this->logger->info('Frontend watcher stopped.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        parent::start();

        if ($this->hasWatchCommand()) {
            $program_name = $this->getWatchProgramName();

            try {
                $this->supervisor->startProcess($program_name);
                $this->logger->info('Frontend watcher started.', ['station_id' => $this->station->getId(), 'station_name' => $this->station->getName()]);
            } catch (FaultException $e) {
                $this->_handleSupervisorException($e, $program_name);
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

    protected function _getRadioPort()
    {
        return (8000 + (($this->station->getId() - 1) * 10));
    }
}
