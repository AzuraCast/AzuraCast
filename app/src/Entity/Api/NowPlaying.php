<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class NowPlaying
{
    /**
     * Station
     *
     * @SWG\Property
     * @var Station
     */
    public $station;

    /**
     * Listener details
     *
     * @SWG\Property
     * @var NowPlayingListeners
     */
    public $listeners;

    /**
     * Live broadcast details
     *
     * @SWG\Property
     * @var NowPlayingLive
     */
    public $live;

    /**
     * Current Song
     *
     * @SWG\Property
     * @var NowPlayingCurrentSong
     */
    public $now_playing;

    /**
     * Next Playing Song
     *
     * @SWG\Property
     * @var SongHistory
     */
    public $playing_next;

    /**
     * @SWG\Property
     * @var SongHistory[]
     */
    public $song_history;

    /**
     * Debugging information about where the now playing data comes from.
     *
     * @SWG\Property(enum={"hit", "database", "station"})
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