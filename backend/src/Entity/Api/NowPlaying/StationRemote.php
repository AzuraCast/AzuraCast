<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use App\Entity\Api\ResolvableUrl;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_StationRemote',
    required: ['*'],
    type: 'object'
)]
class StationRemote
{
    #[OA\Property(
        description: 'Mount/Remote ID number.',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'Mount point name/URL',
        example: '/radio.mp3'
    )]
    public string $name;

    #[OA\Property(
        description: 'Full listening URL specific to this mount',
        type: 'string',
        example: 'http://localhost:8000/radio.mp3'
    )]
    public ResolvableUrl $url;

    #[OA\Property(
        description: 'Bitrate (kbps) of the broadcasted audio (if known)',
        example: 128
    )]
    public ?int $bitrate = null;

    #[OA\Property(
        description: 'Audio encoding format of broadcasted audio (if known)',
        example: 'mp3'
    )]
    public ?string $format = null;

    #[OA\Property]
    public Listeners $listeners;
}
