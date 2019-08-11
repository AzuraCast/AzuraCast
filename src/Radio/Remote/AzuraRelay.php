<?php
namespace App\Radio\Remote;

use App\Entity;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;

class AzuraRelay extends AbstractRemote
{
    public function updateNowPlaying(Entity\StationRemote $remote, $np_aggregate, bool $include_clients = false): array
    {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Entity\Relay) {
            throw new \InvalidArgumentException('AzuraRelay remote must have a corresponding relay.');
        }

        $relay_np = $relay->getNowplaying();

        if (isset($relay_np[$station->getId()][$remote->getMount()])) {
            $np_new = $relay_np[$station->getId()][$remote->getMount()];

            $clients = ($include_clients)
                ? $np_new['listeners']['clients']
                : null;

            $this->_mergeNowPlaying(
                $remote,
                $np_aggregate,
                $np_new,
                $clients
            );
        }

        return $np_aggregate;
    }

    /**
     * @inheritDoc
     */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        $station = $remote->getStation();
        $relay = $remote->getRelay();

        if (!$relay instanceof Entity\Relay) {
            throw new \InvalidArgumentException('AzuraRelay remote must have a corresponding relay.');
        }

        $base_url = new Uri($relay->getBaseUrl());

        $fe_config = (array)$station->getFrontendConfig();
        $radio_port = $fe_config['port'];

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $use_radio_proxy = $settings_repo->getSetting('use_radio_proxy', 0);

        if ( $use_radio_proxy
            || (!APP_IN_PRODUCTION && !APP_INSIDE_DOCKER)
            || 'https' === $base_url->getScheme()) {
            // Web proxy support.
            return (string)$base_url
                ->withPath($base_url->getPath().'/radio/' . $radio_port . $remote->getMount());
        } else {
            // Remove port number and other decorations.
            return (string)$base_url
                ->withPort($radio_port)
                ->withPath($remote->getMount());
        }
    }
}
