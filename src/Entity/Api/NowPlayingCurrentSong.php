<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity;
use App\Traits\LoadFromParentObject;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_NowPlayingCurrentSong")
 */
class NowPlayingCurrentSong extends SongHistory
{
    use LoadFromParentObject;

    /**
     * Elapsed time of the song's playback since it started.
     *
     * @OA\Property(example=25)
     * @var int
     */
    public int $elapsed = 0;

    /**
     * Remaining time in the song, in seconds.
     *
     * @OA\Property(example=155)
     * @var int
     */
    public int $remaining = 0;

    /**
     * Update the "elapsed" and "remaining" timers based on the exact current second.
     */
    public function recalculate(): void
    {
        $this->elapsed = time() + Entity\SongHistory::PLAYBACK_DELAY_SECONDS - $this->played_at;
        if ($this->elapsed < 0) {
            $this->elapsed = 0;
        }

        $this->remaining = 0;

        if ($this->duration !== 0) {
            if ($this->elapsed >= $this->duration) {
                $this->elapsed = $this->duration;
            } else {
                $this->remaining = $this->duration - $this->elapsed;
            }
        }
    }
}
