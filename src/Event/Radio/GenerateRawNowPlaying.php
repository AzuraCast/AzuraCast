<?php
namespace App\Event\Radio;

use App\Entity\Station;
use App\Radio;
use Symfony\Contracts\EventDispatcher\Event;

class GenerateRawNowPlaying extends Event
{
    protected Station $station;

    protected Radio\Frontend\AbstractFrontend $frontend;

    /** @var Radio\Remote\AdapterProxy[] */
    protected array $remotes;

    protected bool $include_clients = false;

    /** @var string|null The preloaded "payload" to supply to the nowplaying adapters, if one is available. */
    protected ?string $payload;

    /** @var array The composed "raw" NowPlaying data. */
    protected array $np_raw = [];

    public function __construct(
        Station $station,
        Radio\Frontend\AbstractFrontend $frontend,
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

    public function getFrontend(): Radio\Frontend\AbstractFrontend
    {
        return $this->frontend;
    }

    /**
     * @return Radio\Remote\AdapterProxy[]
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
