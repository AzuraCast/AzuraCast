<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "PodcastBrandingConfiguration", type: "object")]
final class PodcastBrandingConfiguration extends AbstractArrayEntity
{
    #[OA\Property]
    public ?string $public_custom_html = null {
        set => Types::stringOrNull($value, true);
    }

    #[OA\Property]
    public bool $enable_op3_prefix = false {
        set(bool|string $value) => Types::bool($value, false, true);
    }
}
