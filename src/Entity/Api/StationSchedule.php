<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_StationSchedule")
 */
class StationSchedule
{
    public const TYPE_PLAYLIST = 'playlist';
    public const TYPE_STREAMER = 'streamer';

    /**
     * Unique identifier for this schedule entry.
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * The type of this schedule entry.
     * @OA\Property(enum={App\Entity\Api\StationSchedule::TYPE_PLAYLIST, App\Entity\Api\StationSchedule::TYPE_STREAMER}, example=App\Entity\Api\StationSchedule::TYPE_PLAYLIST)
     */
    public string $type;

    /**
     * Either the playlist or streamer's display name.
     * @OA\Property(example="Example Schedule Entry")
     */
    public string $name;

    /**
     * The full name of the type and name combined.
     * @OA\Property(example="Playlist: Example Schedule Entry")
     */
    public string $title;

    /**
     * The start time of the schedule entry, in UNIX format.
     * @OA\Property(example=1609480800)
     */
    public int $start_timestamp;

    /**
     * The start time of the schedule entry, in ISO 8601 format.
     * @OA\Property(example="020-02-19T03:00:00-06:00")
     */
    public string $start;

    /**
     * The end time of the schedule entry, in UNIX format.
     * @OA\Property(example=1609480800)
     */
    public int $end_timestamp;

    /**
     * The start time of the schedule entry, in ISO 8601 format.
     * @OA\Property(example="020-02-19T05:00:00-06:00")
     */
    public string $end;

    /**
     * Whether the event is currently ongoing.
     * @OA\Property(example=true)
     */
    public bool $is_now;
}
