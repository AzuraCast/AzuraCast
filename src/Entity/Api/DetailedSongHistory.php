<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Traits\LoadFromParentObject;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_DetailedSongHistory")
 */
class DetailedSongHistory extends SongHistory
{
    use LoadFromParentObject;

    /**
     * Number of listeners when the song playback started.
     *
     * @OA\Property(example=94)
     * @var int
     */
    public int $listeners_start = 0;

    /**
     * Number of listeners when song playback ended.
     *
     * @OA\Property(example=105)
     * @var int
     */
    public int $listeners_end = 0;

    /**
     * The sum total change of listeners between the song's start and ending.
     *
     * @OA\Property(example=11)
     * @var int
     */
    public int $delta_total = 0;
}
