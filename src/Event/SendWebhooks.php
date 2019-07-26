<?php
namespace App\Event;

use App\Entity\Api\NowPlaying;
use App\Entity\Station;
use App\Http\Router;
use Symfony\Component\EventDispatcher\Event;
use function DeepCopy\deep_copy;

class SendWebhooks extends Event
{
    public const NAME = 'webhooks-send';

    /** @var Station */
    protected $station;

    /** @var NowPlaying */
    protected $np;

    /** @var Router */
    protected $router;

    /** @var array */
    protected $triggers = [];

    /** @var bool */
    protected $is_standalone = true;

    public function __construct(
        Station $station,
        NowPlaying $np,
        $np_old = null,
        $is_standalone = true)
    {
        $this->station = $station;

        $this->np = $np;
        $this->is_standalone = $is_standalone;

        $to_trigger = ['all'];

        if ($np_old instanceof NowPlaying) {
            if ($np_old->now_playing->song->id !== $np->now_playing->song->id) {
                $to_trigger[] = 'song_changed';
            }

            if ($np_old->listeners->current > $np->listeners->current) {
                $to_trigger[] = 'listener_lost';
            } elseif ($np_old->listeners->current < $np->listeners->current) {
                $to_trigger[] = 'listener_gained';
            }

            if ($np_old->live->is_live === false && $np->live->is_live === true) {
                $to_trigger[] = 'live_connect';
            } elseif ($np_old->live->is_live === true && $np->live->is_live === false) {
                $to_trigger[] = 'live_disconnect';
            }
        }

        $this->triggers = $to_trigger;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return NowPlaying
     */
    public function getNowPlaying(): NowPlaying
    {
        return $this->np;
    }

    /**
     * @return array
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * @param string $trigger_name
     * @return bool
     */
    public function hasTrigger($trigger_name): bool
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

    /**
     * @return bool
     */
    public function isStandalone(): bool
    {
        return $this->is_standalone;
    }
}
