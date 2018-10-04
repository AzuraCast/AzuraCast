<?php

namespace App\Entity\Api;

use App\Entity;
use App\Http\Router;

/**
 * @OA\Schema(type="object")
 */
class NowPlayingCurrentSong extends SongHistory
{
    /**
     * Elapsed time of the song's playback since it started.
     *
     * @OA\Property(example=25)
     * @var int
     */
    public $elapsed;

    /**
     * Remaining time in the song, in seconds.
     *
     * @OA\Property(example=155)
     * @var int
     */
    public $remaining;

    /**
     * Update the "elapsed" and "remaining" timers based on the exact current second.
     *
     * @return void
     */
    public function recalculate()
    {
        $this->elapsed = time() - $this->played_at;
        $this->remaining = 0;

        if ($this->duration !== 0) {
            if ($this->elapsed >= $this->duration) {
                $this->elapsed = $this->duration;
            } else {
                $this->remaining = $this->duration - $this->elapsed;
            }
        }
    }

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param Router $router
     */
    public function resolveUrls(Router $router): void
    {
        $this->song->resolveUrls($router);
    }
}
