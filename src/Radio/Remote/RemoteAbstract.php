<?php
namespace App\Radio\Remote;

use App\Entity;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;

abstract class RemoteAbstract
{
    /** @var Client */
    protected $http_client;

    /** @var Entity\Station */
    protected $station;

    /** @var Entity\StationRemote */
    protected $remote;

    /** @var Logger */
    protected $logger;

    public function __construct(Client $http_client, Logger $logger)
    {
        $this->http_client = $http_client;
        $this->logger = $logger;
    }

    /**
     * @param Entity\Station $station
     */
    public function setStation(Entity\Station $station): void
    {
        $this->station = $station;
    }

    /**
     * @param Entity\StationRemote $remote
     */
    public function setRemote(Entity\StationRemote $remote): void
    {
        $this->remote = $remote;
    }

    /**
     * @return Entity\StationRemote
     */
    public function getRemote(): Entity\StationRemote
    {
        return $this->remote;
    }

    /**
     * @param $np
     * @return bool
     */
    public function updateNowPlaying(&$np, $include_clients = false): bool
    {
        return true;
    }

    /**
     * @param $np
     * @param $adapter_class
     * @param bool $include_clients
     * @return bool
     */
    protected function _updateNowPlayingFromAdapter(&$np, $adapter_class, $include_clients = false): bool
    {
        /** @var \NowPlaying\Adapter\AdapterAbstract $np_adapter */
        $np_adapter = new $adapter_class($this->remote->getUrl(), $this->http_client);

        try {
            $np_new = $np_adapter->getNowPlaying($this->remote->getMount());

            $this->logger->debug('NowPlaying adapter response', ['response' => $np_new]);

            if ($np['meta']['status'] === 'offline' && $np_new['meta']['status'] === 'online') {
                $np['current_song'] = $np_new['current_song'];
                $np['meta'] = $np_new['meta'];
            }

            $np['listeners']['current'] += $np_new['listeners']['current'];
            $np['listeners']['unique'] += $np_new['listeners']['unique'];
            $np['listeners']['total'] += $np_new['listeners']['total'];
            return true;
        } catch(\NowPlaying\Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
            return false;
        }
    }

    /**
     * Return the likely "public" listen URL for the remote.
     *
     * @return string
     */
    public function getPublicUrl(): string
    {
        $custom_listen_url = $this->remote->getCustomListenUrl();

        return (!empty($custom_listen_url))
            ? $custom_listen_url
            : $this->_getRemoteUrl($this->remote->getMount());
    }

    /**
     * Format and return a URL for the remote path.
     *
     * @param string|null $custom_path
     * @return string
     */
    protected function _getRemoteUrl($custom_path = null): string
    {
        $uri = new Uri($this->remote->getUrl());

        return ($custom_path !== null)
            ? (string)$uri->withPath($custom_path)
            : (string)$uri;
    }
}
