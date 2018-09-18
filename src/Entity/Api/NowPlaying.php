<?php

namespace App\Entity\Api;

use App\Entity;

/**
 * @OA\Schema(type="object")
 */
class NowPlaying
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
}
