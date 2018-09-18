<?php
namespace App\Entity\Api;

/**
 * @OA\Schema(type="object")
 */
class DetailedSongHistory extends SongHistory
{
    /**
     * Number of listeners when the song playback started.
     *
     * @OA\Property(example=94)
     * @var int
     */
    public $listeners_start;

    /**
     * Number of listeners when song playback ended.
     *
     * @OA\Property(example=105)
     * @var int
     */
    public $listeners_end;

    /**
     * The sum total change of listeners between the song's start and ending.
     *
     * @OA\Property(example=11)
     * @var int
     */
    public $delta_total;
}
