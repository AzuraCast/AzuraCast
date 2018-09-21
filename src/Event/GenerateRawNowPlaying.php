<?php
namespace App\Event;

use App\Entity\Station;
use App\Radio\Frontend\FrontendAbstract;
use App\Radio\Remote\RemoteAbstract;
use Symfony\Component\EventDispatcher\Event;

class GenerateRawNowPlaying extends Event
{
    const NAME = 'nowplaying-generate-raw';

    /** @var Station */
    protected $station;

    /** @var FrontendAbstract */
    protected $frontend;

    /** @var RemoteAbstract[] */
    protected $remotes;

    /** @var bool */
    protected $include_clients = false;

    /** @var string|null The preloaded "payload" to supply to the nowplaying adapters, if one is available. */
    protected $payload;

    /** @var array The composed "raw" NowPlaying data. */
    protected $np_raw = [];

    public function __construct(
        Station $station,
        FrontendAbstract $frontend,
        array $remotes,
        $payload = null,
        $include_clients = false
    ) {
        $this->station = $station;
        $this->frontend = $frontend;
        $this->remotes = $remotes;
        $this->payload = $payload;
        $this->include_clients = $include_clients;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getFrontend(): FrontendAbstract
    {
        return $this->frontend;
    }

    /**
     * @return RemoteAbstract[]
     */
    public function getRemotes(): array
    {
        return $this->remotes;
    }

    public function includeClients(): bool
    {
        return $this->include_clients;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function getRawResponse(): array
    {
        return $this->np_raw;
    }

    public function setRawResponse(array $np): void
    {
        $this->np_raw = $np;
    }
}
