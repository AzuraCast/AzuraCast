<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationPlaylistComputedFields',
    type: 'object'
)]
final class StationPlaylistComputedFields
{
    #[OA\Property(
        description: 'A URL-safe version of the playlist name.',
        readOnly: true
    )]
    public string $short_name;

    #[OA\Property(
        description: 'The number of songs in the playlist, if it is a song-based playlist.',
        example: 25,
        readOnly: true
    )]
    public ?int $num_songs = null;

    #[OA\Property(
        description: 'The total length of the playlist in seconds, if it is a song-based playlist.',
        example: 3600,
        readOnly: true
    )]
    public ?float $total_length = null;

    /**
     * @var StationPlaylistParentGroup[]
     */
    #[OA\Property(
        description: 'The parent groups that this playlist is a member of.',
        type: 'array',
        items: new OA\Items(ref: StationPlaylistParentGroup::class),
        readOnly: true
    )]
    public array $playlist_groups = [];
}
