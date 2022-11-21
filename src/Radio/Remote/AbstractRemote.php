<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Monolog\Logger;
use NowPlaying\AdapterFactory;
use NowPlaying\Result\Result;

abstract class AbstractRemote
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\SettingsRepository $settingsRepo,
        protected Client $http_client,
        protected Logger $logger,
        protected AdapterFactory $adapterFactory
    ) {
    }

    public function getNowPlayingAsync(
        Entity\StationRemote $remote,
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

    abstract protected function getAdapterType(): string;

    /**
     * Return the likely "public" listen URL for the remote.
     *
     * @param Entity\StationRemote $remote
     */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        $custom_listen_url = $remote->getCustomListenUrl();

        return (!empty($custom_listen_url))
            ? $custom_listen_url
            : $this->getRemoteUrl($remote, $remote->getMount());
    }

    /**
     * Format and return a URL for the remote path.
     *
     * @param Entity\StationRemote $remote
     * @param string|null $customPath
     */
    protected function getRemoteUrl(Entity\StationRemote $remote, ?string $customPath = null): string
    {
        $uri = $remote->getUrlAsUri();

        if (!empty($customPath)) {
            if (!str_starts_with($customPath, '/')) {
                $customPath = '/' . $customPath;
            }
            return (string)$uri->withPath($uri->getPath() . $customPath);
        }

        return (string)$uri;
    }
}
