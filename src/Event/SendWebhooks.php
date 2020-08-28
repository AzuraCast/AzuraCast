<?php
namespace App\Event;

use App\Entity\Api\NowPlaying;
use App\Entity\Station;
use Symfony\Contracts\EventDispatcher\Event;

class SendWebhooks extends Event
{
    protected Station $station;

    protected NowPlaying $np;

    protected array $triggers = [];

    protected bool $is_standalone = true;

    public function __construct(
        Station $station,
        NowPlaying $np,
        bool $is_standalone = true,
        ?array $triggers = []
    ) {
        $this->station = $station;

        $this->np = $np;
        $this->is_standalone = $is_standalone;

        if (empty($triggers)) {
            $triggers = ['all'];
        }
        $this->triggers = $triggers;
    }

    public function computeTriggers($np_old): void
    {
        $to_trigger = ['all'];

        if ($np_old instanceof NowPlaying) {
            if ($np_old->now_playing->song->id !== $this->np->now_playing->song->id) {
                $to_trigger[] = 'song_changed';
            }

            if ($np_old->listeners->current > $this->np->listeners->current) {
                $to_trigger[] = 'listener_lost';
            } elseif ($np_old->listeners->current < $this->np->listeners->current) {
                $to_trigger[] = 'listener_gained';
            }

            if ($np_old->live->is_live === false && $this->np->live->is_live === true) {
                $to_trigger[] = 'live_connect';
            } elseif ($np_old->live->is_live === true && $this->np->live->is_live === false) {
                $to_trigger[] = 'live_disconnect';
            }
        }

        $this->triggers = $to_trigger;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getNowPlaying(): NowPlaying
    {
        return $this->np;
    }

    public function getTriggers(): array
    {
        return $this->triggers;
    }

    public function hasTrigger(string $trigger_name): bool
    {
        return in_array($trigger_name, $this->triggers, true);
    }

    /**
     * @return bool Whether this event has any triggers (besides the default "all").
     */
    public function hasAnyTrigger(): bool
    {
        return count($this->triggers) > 1;
    }

    public function isStandalone(): bool
    {
        return $this->is_standalone;
    }
}
