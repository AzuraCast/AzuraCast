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
    public string $id;

    #[OA\Property]
    public string $title;

    #[OA\Property]
    public ?string $link = null;

    #[OA\Property]
    public string $description;

    #[OA\Property]
    public string $description_short;

    #[OA\Property]
    public bool $explicit = false;

    #[OA\Property]
    public ?int $season_number = null;

    #[OA\Property]
    public ?int $episode_number = null;

    #[OA\Property]
    public int $created_at;

    #[OA\Property]
    public int $publish_at;

    #[OA\Property]
    public bool $is_published = true;

    #[OA\Property]
    public bool $has_media = false;

    #[OA\Property]
    public ?string $playlist_media_id = null;

    #[OA\Property]
    public ?Song $playlist_media = null;

    #[OA\Property]
    public ?PodcastMedia $media = null;

    #[OA\Property]
    public bool $has_custom_art = false;

    #[OA\Property]
    public ?string $art = null;

    #[OA\Property]
    public int $art_updated_at = 0;
}
