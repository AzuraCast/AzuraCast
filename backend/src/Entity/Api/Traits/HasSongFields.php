<?php

declare(strict_types=1);

namespace App\Entity\Api\Traits;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Api_HasSongFields', type: 'object')]
trait HasSongFields
{
    #[OA\Property(
        description: 'The song title, usually "Artist - Title"',
        example: 'Chet Porter - Aluko River'
    )]
    public string $text = '';

    #[OA\Property(
        description: 'The song artist.',
        example: 'Chet Porter'
    )]
    public ?string $artist = '';

    #[OA\Property(
        description: 'The song title.',
        example: 'Aluko River'
    )]
    public ?string $title = '';

    #[OA\Property(
        description: 'The song album.',
        example: 'Moving Castle'
    )]
    public ?string $album = '';

    #[OA\Property(
        description: 'The song genre.',
        example: 'Rock'
    )]
    public ?string $genre = '';

    #[OA\Property(
        description: 'The International Standard Recording Code (ISRC) of the file.',
        example: 'US28E1600021'
    )]
    public ?string $isrc = '';

    #[OA\Property(
        description: 'Lyrics to the song.',
        example: ''
    )]
    public ?string $lyrics = '';
}
