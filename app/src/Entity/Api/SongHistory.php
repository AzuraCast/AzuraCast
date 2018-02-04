<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class SongHistory
{
    /**
     * Song history unique identifier
     *
     * @SWG\Property
     * @var int
     */
    public $sh_id;

    /**
     * UNIX timestamp when playback started.
     *
     * @SWG\Property(example=SAMPLE_TIMESTAMP)
     * @var int
     */
    public $played_at;

    /**
     * Duration of the song in seconds
     *
     * @SWG\Property(example=180)
     * @var int
     */
    public $duration;

    /**
     * Indicates the playlist that the song was played from, if available, or empty string if not.
     *
     * @SWG\Property(example="Top 100")
     * @var string
     */
    public $playlist;

    /**
     * Indicates whether the song is a listener request.
     *
     * @SWG\Property
     * @var bool
     */
    public $is_request;

    /**
     * Song
     *
     * @SWG\Property
     * @var Song
     */
    public $song;


}