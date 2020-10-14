<?php

namespace App\Radio\Remote;

use App\Entity;
use App\Settings;
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

            $npNew = Result::fromArray($npRaw);

            $this->logger->debug(
                'Response for remote relay',
                ['remote' => $remote->getDisplayName(), 'response' => $npNew]
            );

            $remote->setListenersTotal($np->listeners->total);
            $remote->setListenersUnique($np->listeners->unique);
            $this->em->persist($remote);
            $this->em->flush();

            return $np->merge($npNew);
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

        $base_url = new Uri($relay->getBaseUrl());

        $fe_config = $station->getFrontendConfig();
        $radio_port = $fe_config->getPort();

        $use_radio_proxy = $this->settingsRepo->getSetting('use_radio_proxy', 0);

        if (
            $use_radio_proxy
            || (!Settings::getInstance()->isProduction() && !Settings::getInstance()->isDocker())
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
