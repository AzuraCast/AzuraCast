<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Http\Router;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_Song',
    type: 'object'
)]
class Song implements ResolvableUrlInterface
{
    #[OA\Property(
        description: 'The song\'s 32-character unique identifier hash',
        example: '9f33bbc912c19603e51be8e0987d076b'
    )]
    public string $id = '';

    #[OA\Property(
        description: 'The song title, usually "Artist - Title"',
        example: 'Chet Porter - Aluko River'
    )]
    public string $text = '';

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

    #[OA\Property(
        description: 'The song album.',
        example: 'Moving Castle'
    )]
    public string $album = '';

    #[OA\Property(
        description: 'The song genre.',
        example: 'Rock'
    )]
    public string $genre = '';

    #[OA\Property(
        description: 'The International Standard Recording Code (ISRC) of the file.',
        example: 'US28E1600021'
    )]
    public string $isrc = '';

    #[OA\Property(
        description: 'Lyrics to the song.',
        example: ''
    )]
    public string $lyrics = '';

    #[OA\Property(
        description: 'URL to the album artwork (if available).',
        example: 'https://picsum.photos/1200/1200'
    )]
    public string|UriInterface $art = '';

    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string', example: 'custom_field_value')
    )]
    public array $custom_fields = [];

    /**
     * Re-resolve any Uri instances to reflect base URL changes.
     *
     * @param UriInterface $base
     */
    public function resolveUrls(UriInterface $base): void
    {
        $this->art = (string)Router::resolveUri($base, $this->art, true);
    }
}
