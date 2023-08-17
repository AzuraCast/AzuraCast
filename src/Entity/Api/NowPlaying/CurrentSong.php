<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\SongHistory as SongHistoryEntity;
use App\Traits\LoadFromParentObject;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_CurrentSong',
    type: 'object'
)]
final class CurrentSong extends SongHistory
{
    use LoadFromParentObject;

    #[OA\Property(
        description: 'Elapsed time of the song\'s playback since it started.',
        example: 25
    )]
    public int $elapsed = 0;

    #[OA\Property(
        description: 'Remaining time in the song, in seconds.',
        example: 155
    )]
    public int $remaining = 0;

    /**
     * Update the "elapsed" and "remaining" timers based on the exact current second.
     */
    public function recalculate(): void
    {
        $this->elapsed = time() + SongHistoryEntity::PLAYBACK_DELAY_SECONDS - $this->played_at;
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
