<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Entity\StationRemote;
use App\Radio\Adapters;
use App\Radio\Frontend\AbstractFrontend;
use App\Radio\Remote\AbstractRemote;
use NowPlaying\Result\Result;
use Symfony\Contracts\EventDispatcher\Event;
use Traversable;

final class GenerateRawNowPlaying extends Event
{
    private ?Result $result = null;

    public function __construct(
        private readonly Adapters $adapters,
        private readonly Station $station,
        private readonly bool $includeClients = false
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

    /**
     * @return Traversable<StationRemote>
     */
    public function getRemotes(): Traversable
    {
        return $this->station->getRemotes();
    }

    public function getRemoteAdapter(StationRemote $remote): AbstractRemote
    {
        return $this->adapters->getRemoteAdapter($remote);
    }

    public function includeClients(): bool
    {
        return $this->includeClients;
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
