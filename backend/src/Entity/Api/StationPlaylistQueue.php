<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationPlaylistQueue',
    type: 'object'
)]
final class StationPlaylistQueue
{
    #[OA\Property(
        description: 'ID of the StationPlaylistMedia record associating this track with the playlist',
        example: 1
    )]
    public ?int $spm_id = null;

    #[OA\Property(
        description: 'ID of the StationPlaylistMedia record associating this track with the playlist',
        example: 1
    )]
    public int $media_id;

    #[OA\Property(
        description: 'The song\'s 32-character unique identifier hash',
        example: '9f33bbc912c19603e51be8e0987d076b'
    )]
    public string $song_id;

    #[OA\Property(
        description: 'The song artist.',
        example: 'Chet Porter'
    )]
    public string $artist = '';

    #[OA\Property(
        description: 'The song title.',
        example: 'Aluko River'
    )]
    public string $title = '';
}
