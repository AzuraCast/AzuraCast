<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_PodcastMedia',
    type: 'object'
)]
final class PodcastMedia
{
    #[OA\Property]
    public ?string $id = null;

    #[OA\Property]
    public ?string $original_name = null;

    #[OA\Property]
    public float $length = 0.0;

    #[OA\Property]
    public ?string $length_text = null;

    #[OA\Property]
    public ?string $path = null;
}
