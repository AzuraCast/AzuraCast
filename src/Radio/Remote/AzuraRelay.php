<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Cache\AzuraRelayCache;
use App\Entity;
use App\Environment;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Monolog\Logger;
use NowPlaying\AdapterFactory;
use NowPlaying\Enums\AdapterTypes;
use NowPlaying\Result\Result;

final class AzuraRelay extends AbstractRemote
{
    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Client $http_client,
        Logger $logger,
        AdapterFactory $adapterFactory,
        private readonly AzuraRelayCache $azuraRelayCache
    ) {
        parent::__construct($em, $settingsRepo, $http_client, $logger, $adapterFactory);
    }

    public function getNowPlayingAsync(Entity\StationRemote $remote, bool $includeClients = false): PromiseInterface
    {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Entity\Relay) {
            throw new InvalidArgumentException('AzuraRelay remote must have a corresponding relay.');
        }

        $npRawRelay = $this->azuraRelayCache->getForRelay($relay);

        if (isset($npRawRelay[$station->getId()][$remote->getMount()])) {
            $npRaw = $npRawRelay[$station->getId()][$remote->getMount()];

            $result = Result::fromArray($npRaw);

            if (!empty($result->clients)) {
                foreach ($result->clients as $client) {
                    $client->mount = 'remote_' . $remote->getId();
                }
            }

            $this->logger->debug(
                'Response for remote relay',
                ['remote' => $remote->getDisplayName(), 'response' => $result]
            );

            $remote->setListenersTotal($result->listeners->total);
            $remote->setListenersUnique($result->listeners->unique ?? 0);
            $this->em->persist($remote);

            return Create::promiseFor($result);
        }

        return Create::promiseFor(null);
    }

    protected function getAdapterType(): AdapterTypes
    {
        // Not used for this adapter.
        return AdapterTypes::Icecast;
    }

    /**
     * @inheritDoc
     */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Entity\Relay) {
            throw new InvalidArgumentException('AzuraRelay remote must have a corresponding relay.');
        }

        $base_url = new Uri(rtrim($relay->getBaseUrl(), '/'));

        $radio_port = $station->getFrontendConfig()->getPort();

        $use_radio_proxy = $this->settingsRepo->readSettings()->getUseRadioProxy();

        if (
            $use_radio_proxy
            || 'https' === $base_url->getScheme()
            || (!Environment::getInstance()->isProduction() && !Environment::getInstance()->isDocker())
        ) {
            // Web proxy support.
            return (string)$base_url
                ->withPath($base_url->getPath() . '/radio/' . $radio_port . $remote->getMount());
        }

        // Remove port number and other decorations.
        return (string)$base_url
            ->withPort($radio_port)
            ->withPath($remote->getMount() ?? '');
    }
}
