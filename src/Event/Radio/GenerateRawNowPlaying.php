<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Radio\Adapters;
use App\Radio\Frontend\AbstractFrontend;
use NowPlaying\Result\Result;
use Symfony\Contracts\EventDispatcher\Event;

class GenerateRawNowPlaying extends Event
{
    protected ?Result $result = null;

    public function __construct(
        protected Adapters $adapters,
        protected Station $station,
        protected bool $include_clients = false
    ) {
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getFrontend(): ?AbstractFrontend
    {
        return $this->adapters->getFrontendAdapter($this->station);
    }

    public function getRemotes(): array
    {
        $remotes = [];
        foreach ($this->station->getRemotes() as $remote) {
            $remotes[] = [
                $remote,
                $this->adapters->getRemoteAdapter($this->station, $remote),
            ];
        }
        return $remotes;
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
