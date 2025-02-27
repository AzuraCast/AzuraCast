<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasSongFields;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Song',
    type: 'object'
)]
class Song
{
    use HasSongFields;

    #[OA\Property(
        description: 'The song\'s 32-character unique identifier hash',
        example: '9f33bbc912c19603e51be8e0987d076b'
    )]
    public string $id = '';

    #[OA\Property(
        description: 'URL to the album artwork (if available).',
        type: 'string',
        example: 'https://picsum.photos/1200/1200'
    )]
    public ResolvableUrl $art;

    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string', example: 'custom_field_value')
    )]
    public array $custom_fields = [];
}
