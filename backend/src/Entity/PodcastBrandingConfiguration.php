<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Types;

class PodcastBrandingConfiguration extends AbstractArrayEntity
{
    public ?string $public_custom_html {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public bool $enable_op3_prefix {
        get => Types::bool($this->get(__PROPERTY__), false, true);
        set(bool|string $value) {
            $this->set(__PROPERTY__, Types::bool($value, false, true));
        }
    }
}
