<?php
namespace App\Radio\Remote;

use App\Entity;
use App\Settings;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;
use NowPlaying\Adapter\AdapterAbstract;
use NowPlaying\Exception;

abstract class AbstractRemote
{
    /** @var EntityManager */
    protected $em;

    /** @var Client */
    protected $http_client;

    /** @var Logger */
    protected $logger;

    public function __construct(
        EntityManager $em,
        Client $http_client,
        Logger $logger
    ) {
        $this->em = $em;
        $this->http_client = $http_client;
        $this->logger = $logger;
    }

    /**
     * @param Entity\StationRemote $remote
     * @param array $np_aggregate
     * @param bool $include_clients
     * @return array The aggregated now-playing result.
     */
    public function updateNowPlaying(
        Entity\StationRemote $remote,
        $np_aggregate,
        bool $include_clients = false
    ): array {
        return $np_aggregate;
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

    /**
     * @param Entity\StationRemote $remote
     * @param array $np_aggregate
     * @param string $adapter_class
     * @param bool $include_clients
     * @return array The resulting aggregated now-playing response.
     */
    protected function _updateNowPlayingFromAdapter(
        Entity\StationRemote $remote,
        $np_aggregate,
        $adapter_class,
        bool $include_clients = false
    ): array {
        /** @var AdapterAbstract $np_adapter */
        $np_adapter = new $adapter_class($remote->getUrl(), $this->http_client);

        try {
            $np = $np_adapter->getNowPlaying($remote->getMount());
            $this->logger->debug('NowPlaying adapter response', ['response' => $np]);

            return $this->_mergeNowPlaying(
                $remote,
                $np_aggregate,
                $np,
                null
            );
        } catch (Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));
        }

        return $np_aggregate;
    }

    /**
     * @param Entity\StationRemote $remote
     * @param array $np_aggregate
     * @param array $np
     * @param array|null $clients
     *
     * @return array The composed aggregate now-playing response.
     */
    protected function _mergeNowPlaying(
        Entity\StationRemote $remote,
        array $np_aggregate,
        array $np,
        ?array $clients
    ): array {
        if (null !== $clients) {
            $original_num_clients = count($clients);

            $np['listeners']['clients'] = Entity\Listener::filterClients($clients);

            $num_clients = count($np['listeners']['clients']);

            // If clients were filtered out, remove them from the listener count as well.
            if ($num_clients < $original_num_clients) {
                $client_diff = $original_num_clients - $num_clients;
                $np['listeners']['total'] -= $client_diff;
            }

            $np['listeners']['unique'] = $num_clients;
            $np['listeners']['current'] = $num_clients;

            if ($np['listeners']['unique'] > $np['listeners']['total']) {
                $np['listeners']['total'] = $np['listeners']['unique'];
            }
        } else {
            $np['listeners']['clients'] = [];
        }

        $this->logger->debug('Response for remote relay', ['remote' => $remote->getDisplayName(), 'response' => $np]);

        $remote->setListenersTotal($np['listeners']['total']);
        $remote->setListenersUnique($np['listeners']['unique']);
        $this->em->persist($remote);
        $this->em->flush($remote);

        if ($np_aggregate['meta']['status'] === 'offline' && $np['meta']['status'] === 'online') {
            $np_aggregate['current_song'] = $np['current_song'];
            $np_aggregate['meta'] = $np['meta'];
        }

        $np_aggregate['listeners']['clients'] = array_merge((array)$np_aggregate['listeners']['clients'],
            (array)$np['listeners']['clients']);
        $np_aggregate['listeners']['current'] += $np['listeners']['current'];
        $np_aggregate['listeners']['unique'] += $np['listeners']['unique'];
        $np_aggregate['listeners']['total'] += $np['listeners']['total'];

        return $np_aggregate;
    }
}
