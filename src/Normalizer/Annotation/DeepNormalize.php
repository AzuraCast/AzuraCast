<?php

namespace App\Normalizer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class DeepNormalize
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
