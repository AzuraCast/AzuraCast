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
     * Current Song
     *
     * @SWG\Property
     * @var SongHistory
     */
    public $now_playing;

    /**
     * Listener details
     *
     * @SWG\Property
     * @var NowPlayingListeners
     */
    public $listeners;

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
}