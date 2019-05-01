<?php
namespace App\Entity\Api;

use OpenApi\Annotations as OA;
use App\Entity;
use Azura\Http\Router;

/**
 * @OA\Schema(type="object", schema="Api_NowPlaying")
 */
class NowPlaying implements ResolvableUrlInterface
{
    /**
     * Station
     *
     * @OA\Property
     * @var Station
     */
    public $station;

    /**
     * Listener details
     *
     * @OA\Property
     * @var NowPlayingListeners
     */
    public $listeners;

    /**
     * Live broadcast details
     *
     * @OA\Property
     * @var NowPlayingLive
     */
    public $live;

    /**
     * Current Song
     *
     * @OA\Property
     * @var NowPlayingCurrentSong
     */
    public $now_playing;

    /**
     * Next Playing Song
     *
     * @OA\Property
     * @var SongHistory
     */
    public $playing_next;

    /**
     * @OA\Property
     * @var SongHistory[]
     */
    public $song_history;

    /**
     * Debugging information about where the now playing data comes from.
     *
     * @OA\Property(enum={"hit", "database", "station"})
     * @var string
     */
    public $cache;

    /**
     * Update any variable items in the feed.
     */
    public function update()
    {
        $this->now_playing->recalculate();
    }

    /**
     * Return an array representation of this object.
     *
     * @return array
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * Implement special cloning rules.
     */
    public function __clone()
    {
        $this->station = clone $this->station;
        $this->now_playing = clone $this->now_playing;
        $this->playing_next = clone $this->playing_next;

        $new_history = [];
        foreach($this->song_history as $history_obj) {
            $new_history[] = clone $history_obj;
        }
        $this->song_history = $new_history;
    }

    /**
     * Iterate through sub-items and re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        if ($this->station instanceof ResolvableUrlInterface) {
            $this->station->resolveUrls($router);
        }

        if ($this->now_playing instanceof ResolvableUrlInterface) {
            $this->now_playing->resolveUrls($router);
        }

        if ($this->playing_next instanceof ResolvableUrlInterface) {
            $this->playing_next->resolveUrls($router);
        }

        foreach($this->song_history as $history_obj) {
            if ($history_obj instanceof ResolvableUrlInterface) {
                $history_obj->resolveUrls($router);
            }
        }
    }
}
