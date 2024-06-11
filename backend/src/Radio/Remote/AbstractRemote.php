<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\StationRemote;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use NowPlaying\AdapterFactory;
use NowPlaying\Enums\AdapterTypes;
use NowPlaying\Result\Result;

abstract class AbstractRemote
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        protected Client $httpClient,
        protected AdapterFactory $adapterFactory
    ) {
    }

    public function getNowPlayingAsync(
        StationRemote $remote,
        bool $includeClients = false
    ): PromiseInterface {
        $adapterType = $this->getAdapterType();

        $npAdapter = $this->adapterFactory->getAdapter(
            $adapterType,
            $remote->getUrl()
        );

        $npAdapter->setAdminPassword($remote->getAdminPassword());

        return $npAdapter->getNowPlayingAsync($remote->getMount(), $includeClients)->then(
            function (Result $result) use ($remote) {
                if (!empty($result->clients)) {
                    foreach ($result->clients as $client) {
                        $client->mount = 'remote_' . $remote->getId();
                    }
                }

                $this->logger->debug('NowPlaying adapter response', ['response' => $result]);

                $remote->setListenersTotal($result->listeners->total);
                $remote->setListenersUnique($result->listeners->unique ?? 0);
                $this->em->persist($remote);

                return $result;
            }
        );
    }

    abstract protected function getAdapterType(): AdapterTypes;

    /**
     * Return the likely "public" listen URL for the remote.
     *
     * @param StationRemote $remote
     */
    public function getPublicUrl(StationRemote $remote): string
    {
        $customListenUrl = $remote->getCustomListenUrl();

        return (!empty($customListenUrl))
            ? $customListenUrl
            : $this->getRemoteUrl($remote, $remote->getMount());
    }

    /**
     * Format and return a URL for the remote path.
     *
     * @param StationRemote $remote
     * @param string|null $customPath
     */
    protected function getRemoteUrl(StationRemote $remote, ?string $customPath = null): string
    {
        $uri = $remote->getUrlAsUri();

        if (!empty($customPath)) {
            return (string)$uri->withPath(
                rtrim($uri->getPath(), '/')
                . '/'
                . ltrim($customPath, '/')
            );
        }

        return (string)$uri;
    }
}
