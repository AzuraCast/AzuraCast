<?php

declare(strict_types=1);

namespace App\Normalizer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class DeepNormalize
{
    private bool $deepNormalize;

    public function __construct(bool $value)
    {
        $this->deepNormalize = $value;
    }

    public function getDeepNormalize(): bool
    {
        return $this->deepNormalize;
    }
}
