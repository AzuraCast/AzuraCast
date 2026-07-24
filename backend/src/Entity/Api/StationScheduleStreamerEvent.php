<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationScheduleStreamerEvent',
    required: ['*'],
    type: 'object'
)]
final class StationScheduleStreamerEvent
{
    #[OA\Property(
        description: 'The unique identifier of the streamer.',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'The streamer display name representing the event title.',
        example: 'Example Streamer'
    )]
    public string $title;

    #[OA\Property(
        description: 'The type of this schedule event.',
        enum: ['streamer'],
        example: 'streamer'
    )]
    public string $type = 'streamer';

    #[OA\Property(
        description: 'The start time of this schedule event, in ISO 8601 format.',
        example: '2020-02-19T03:00:00-06:00'
    )]
    public string $start;

    #[OA\Property(
        description: 'The end time of this schedule event, in ISO 8601 format.',
        example: '2020-02-19T05:00:00-06:00'
    )]
    public string $end;

    #[OA\Property(
        description: 'The API URL used to edit the underlying streamer.',
        example: '/api/station/1/streamer/1'
    )]
    public string $edit_url;

    #[OA\Property(
        description: 'The streamer login username.',
        example: 'example_dj'
    )]
    public string $streamer_username;

    #[OA\Property(
        description: 'Comments about the streamer.',
        example: 'Live every weekday evening.'
    )]
    public ?string $comments = null;

    #[OA\Property(
        description: 'Whether the streamer has custom artwork.',
        example: true
    )]
    public bool $has_custom_art;

    #[OA\Property(
        description: 'The URL of the streamer artwork, if custom artwork is present.',
        example: '/api/station/1/streamer/1/art/1.jpg'
    )]
    public ?string $art = null;
}
