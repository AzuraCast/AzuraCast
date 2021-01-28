<?php

namespace App\Radio\Remote;

use App\Entity;
use App\Environment;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use NowPlaying\Result\Result;

class AzuraRelay extends AbstractRemote
{
    public function updateNowPlaying(
        Result $np,
        Entity\StationRemote $remote,
        bool $includeClients = false
    ): Result {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Entity\Relay) {
            throw new InvalidArgumentException('AzuraRelay remote must have a corresponding relay.');
        }

        $npRawRelay = $relay->getNowplaying();

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
            $remote->setListenersUnique($result->listeners->unique);
            $this->em->persist($remote);
            $this->em->flush();

            return $np->merge($result);
        }

        return $np;
    }

    protected function getAdapterType(): string
    {
        return '';
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

        $fe_config = $station->getFrontendConfig();
        $radio_port = $fe_config->getPort();

        $use_radio_proxy = $this->settings->getUseRadioProxy();

        if (
            $use_radio_proxy
            || (!Environment::getInstance()->isProduction() && !Environment::getInstance()->isDocker())
            || 'https' === $base_url->getScheme()
        ) {
            // Web proxy support.
            return (string)$base_url
                ->withPath($base_url->getPath() . '/radio/' . $radio_port . $remote->getMount());
        }

        // Remove port number and other decorations.
        return (string)$base_url
            ->withPort($radio_port)
            ->withPath($remote->getMount());
    }
}
