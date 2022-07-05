<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_PodcastEpisode',
    type: 'object'
)]
final class PodcastEpisode
{
    use HasLinks;

    #[OA\Property]
    public ?string $id = null;

    #[OA\Property]
    public ?string $title = null;

    #[OA\Property]
    public ?string $description = null;

    #[OA\Property]
    public bool $explicit = false;

    #[OA\Property]
    public ?int $publish_at = null;

    #[OA\Property]
    public bool $has_media = false;

    #[OA\Property]
    public PodcastMedia $media;

    #[OA\Property]
    public bool $has_custom_art = false;

    #[OA\Property]
    public ?string $art = null;

    #[OA\Property]
    public int $art_updated_at = 0;
}
