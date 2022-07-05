<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Podcast',
    type: 'object'
)]
final class Podcast
{
    use HasLinks;

    #[OA\Property]
    public ?string $id = null;

    #[OA\Property]
    public ?int $storage_location_id = null;

    #[OA\Property]
    public ?string $title = null;

    #[OA\Property]
    public ?string $link = null;

    #[OA\Property]
    public ?string $description = null;

    #[OA\Property]
    public ?string $language = null;

    #[OA\Property]
    public ?string $author = null;

    #[OA\Property]
    public ?string $email = null;

    #[OA\Property]
    public bool $has_custom_art = false;

    #[OA\Property]
    public ?string $art = null;

    #[OA\Property]
    public int $art_updated_at = 0;

    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string')
    )]
    public array $categories = [];

    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string')
    )]
    public array $episodes = [];
}
