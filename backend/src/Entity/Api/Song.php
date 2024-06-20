<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasSongFields;
use App\Http\Router;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UriInterface;

#[OA\Schema(
    schema: 'Api_Song',
    type: 'object'
)]
class Song implements ResolvableUrlInterface
{
    use HasSongFields;

    #[OA\Property(
        description: 'The song\'s 32-character unique identifier hash',
        example: '9f33bbc912c19603e51be8e0987d076b'
    )]
    public string $id = '';

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
