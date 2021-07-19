<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Radio;
use NowPlaying\Result\Result;
use Symfony\Contracts\EventDispatcher\Event;

class GenerateRawNowPlaying extends Event
{
    protected ?Result $result = null;

    public function __construct(
        protected Station $station,
        protected Radio\Frontend\AbstractFrontend $frontend,
        protected array $remotes,
        protected bool $include_clients = false
    ) {
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
