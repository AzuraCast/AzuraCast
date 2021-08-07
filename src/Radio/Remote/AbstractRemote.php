<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
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

    /**
     * @param Result $np
     * @param Entity\StationRemote $remote
     * @param bool $includeClients
     *
     * @return Result The aggregated now-playing result.
     */
    public function updateNowPlaying(
        Result $np,
        Entity\StationRemote $remote,
        bool $includeClients = false
    ): Result {
        $adapterType = $this->getAdapterType();

        $npAdapter = $this->adapterFactory->getAdapter(
            $adapterType,
            $remote->getUrl()
        );

        $npAdapter->setAdminPassword($remote->getAdminPassword());

        try {
            $result = $npAdapter->getNowPlaying($remote->getMount(), $includeClients);

            if (!empty($result->clients)) {
                foreach ($result->clients as $client) {
                    $client->mount = 'remote_' . $remote->getId();
                }
            }
        } catch (Exception $e) {
            $this->logger->error(sprintf('NowPlaying adapter error: %s', $e->getMessage()));

            $result = Result::blank();
        }

        $this->logger->debug('NowPlaying adapter response', ['response' => $result]);

        $remote->setListenersTotal($result->listeners->total);
        $remote->setListenersUnique($result->listeners->unique ?? 0);
        $this->em->persist($remote);
        $this->em->flush();

        return $np->merge($result);
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
     * @param string|null $custom_path
     */
    protected function getRemoteUrl(Entity\StationRemote $remote, ?string $custom_path = null): string
    {
        $uri = new Uri($remote->getUrl());

        return (null !== $custom_path)
            ? (string)$uri->withPath($custom_path)
            : (string)$uri;
    }
}
