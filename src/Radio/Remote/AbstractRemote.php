<?php
namespace App\Radio\Remote;

use App\Entity;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;

abstract class AbstractRemote
{
    /** @var Client */
    protected $http_client;

    /** @var Logger */
    protected $logger;

    public function __construct(Client $http_client, Logger $logger)
    {
        $this->http_client = $http_client;
        $this->logger = $logger;
    }

    /**
     * @param Entity\StationRemote $remote
     * @param array $np
     * @param bool $include_clients
     * @return bool
     */
    public function updateNowPlaying(Entity\StationRemote $remote, &$np, $include_clients = false): bool
    {
        return true;
    }

    /**
     * @param Entity\StationRemote $remote
     * @param array $np
     * @param string $adapter_class
     * @param bool $include_clients
     * @return bool
     */
    protected function _updateNowPlayingFromAdapter(Entity\StationRemote $remote, &$np, $adapter_class, $include_clients = false): bool
    {
        /** @var \NowPlaying\Adapter\AdapterAbstract $np_adapter */
        $np_adapter = new $adapter_class($remote->getUrl(), $this->http_client);

        try {
            $np_new = $np_adapter->getNowPlaying($remote->getMount());
            $this->logger->debug('NowPlaying adapter response', ['response' => $np_new]);

            $this->_mergeNowPlaying($np_new, $np);
            return true;
        } catch(\NowPlaying\Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
            return false;
        }
    }

    /**
     * @param array|null $np_new
     * @param array $np
     */
    protected function _mergeNowPlaying($np_new, &$np): void
    {
        if ($np['meta']['status'] === 'offline' && $np_new['meta']['status'] === 'online') {
            $np['current_song'] = $np_new['current_song'];
            $np['meta'] = $np_new['meta'];
        }

        $np['listeners']['current'] += $np_new['listeners']['current'];
        $np['listeners']['unique'] += $np_new['listeners']['unique'];
        $np['listeners']['total'] += $np_new['listeners']['total'];
    }

    /**
     * Return the likely "public" listen URL for the remote.
     *
     * @param Entity\StationRemote $remote
     * @return string
     */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        $custom_listen_url = $remote->getCustomListenUrl();

        return (!empty($custom_listen_url))
            ? $custom_listen_url
            : $this->_getRemoteUrl($remote, $remote->getMount());
    }

    /**
     * Format and return a URL for the remote path.
     *
     * @param Entity\StationRemote $remote
     * @param null $custom_path
     * @return string
     */
    protected function _getRemoteUrl(Entity\StationRemote $remote, $custom_path = null): string
    {
        $uri = new Uri($remote->getUrl());

        return ($custom_path !== null)
            ? (string)$uri->withPath($custom_path)
            : (string)$uri;
    }
}
