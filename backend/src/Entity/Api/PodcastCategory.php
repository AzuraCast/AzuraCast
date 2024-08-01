<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_PodcastCategory',
    type: 'object'
)]
final class PodcastCategory
{
    #[OA\Property]
    public string $category;

    #[OA\Property]
    public string $text;

    #[OA\Property]
    public string $title;

    #[OA\Property]
    public ?string $subtitle = null;
}
