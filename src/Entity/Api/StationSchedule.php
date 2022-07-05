<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\OpenApi;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationSchedule',
    type: 'object'
)]
final class StationSchedule
{
    public const TYPE_PLAYLIST = 'playlist';
    public const TYPE_STREAMER = 'streamer';

    #[OA\Property(
        description: 'Unique identifier for this schedule entry.',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'The type of this schedule entry.',
        enum: [
            StationSchedule::TYPE_PLAYLIST,
            StationSchedule::TYPE_STREAMER,
        ],
        example: StationSchedule::TYPE_PLAYLIST
    )]
    public string $type;

    #[OA\Property(
        description: 'Either the playlist or streamer\'s display name.',
        example: 'Example Schedule Entry'
    )]
    public string $name;

    #[OA\Property(
        description: 'The name of the event.',
        example: 'Example Schedule Entry'
    )]
    public string $title;

    #[OA\Property(
        description: 'The full name of the type and name combined.',
        example: 'Playlist: Example Schedule Entry'
    )]
    public string $description;

    #[OA\Property(
        description: 'The start time of the schedule entry, in UNIX format.',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $start_timestamp;

    #[OA\Property(
        description: 'The start time of the schedule entry, in ISO 8601 format.',
        example: '020-02-19T03:00:00-06:00'
    )]
    public string $start;

    #[OA\Property(
        description: 'The end time of the schedule entry, in UNIX format.',
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $end_timestamp;

    #[OA\Property(
        description: 'The start time of the schedule entry, in ISO 8601 format.',
        example: '020-02-19T05:00:00-06:00'
    )]
    public string $end;

    #[OA\Property(
        description: 'Whether the event is currently ongoing.',
        example: true
    )]
    public bool $is_now;
}
