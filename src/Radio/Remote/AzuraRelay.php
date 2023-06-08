<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Cache\AzuraRelayCache;
use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Relay;
use App\Entity\StationRemote;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use NowPlaying\AdapterFactory;
use NowPlaying\Enums\AdapterTypes;
use NowPlaying\Result\Result;

final class AzuraRelay extends AbstractRemote
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly AzuraRelayCache $azuraRelayCache,
        Client $httpClient,
        AdapterFactory $adapterFactory,
    ) {
        parent::__construct($httpClient, $adapterFactory);
    }

    public function getNowPlayingAsync(StationRemote $remote, bool $includeClients = false): PromiseInterface
    {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Relay) {
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
    public function getPublicUrl(StationRemote $remote): string
    {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Relay) {
            throw new InvalidArgumentException('AzuraRelay remote must have a corresponding relay.');
        }

        $baseUrl = new Uri(rtrim($relay->getBaseUrl(), '/'));

        $radioPort = $station->getFrontendConfig()->getPort();

        $useRadioProxy = $this->readSettings()->getUseRadioProxy();

        if (
            $useRadioProxy
            || 'https' === $baseUrl->getScheme()
            || (!$this->environment->isProduction() && !$this->environment->isDocker())
        ) {
            // Web proxy support.
            return (string)$baseUrl
                ->withPath($baseUrl->getPath() . '/radio/' . $radioPort . $remote->getMount());
        }

        // Remove port number and other decorations.
        return (string)$baseUrl
            ->withPort($radioPort)
            ->withPath($remote->getMount() ?? '');
    }
}
