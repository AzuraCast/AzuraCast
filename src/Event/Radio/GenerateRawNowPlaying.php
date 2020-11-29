<?php

namespace App\Event\Radio;

use App\Entity\Station;
use App\Radio;
use NowPlaying\Result\Result;
use Symfony\Contracts\EventDispatcher\Event;

class GenerateRawNowPlaying extends Event
{
    protected Station $station;

    protected Radio\Frontend\AbstractFrontend $frontend;

    /** @var Radio\Remote\AdapterProxy[] */
    protected array $remotes;

    protected bool $include_clients = false;

    protected ?Result $result = null;

    public function __construct(
        Station $station,
        Radio\Frontend\AbstractFrontend $frontend,
        array $remotes,
        $include_clients = false
    ) {
        $this->station = $station;
        $this->frontend = $frontend;
        $this->remotes = $remotes;
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

    public function getResult(): Result
    {
        return $this->result ?? Result::blank();
    }

    public function setResult(Result $result): void
    {
        $this->result = $result;
    }
}
