<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_StationMount',
    required: ['*'],
    type: 'object'
)]
final class StationMount extends StationRemote
{
    #[OA\Property(
        description: 'The relative path that corresponds to this mount point',
        example: '/radio.mp3'
    )]
    public string $path;

    #[OA\Property(
        description: 'If the mount is the default mount for the parent station',
        example: true
    )]
    public bool $is_default;
}
