<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Types;

final class PodcastBrandingConfiguration extends AbstractArrayEntity
{
    public ?string $public_custom_html = null {
        set => Types::stringOrNull($value, true);
    }

    public bool $enable_op3_prefix = false {
        set(bool|string $value) => Types::bool($value, false, true);
    }
}
