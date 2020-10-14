<?php

namespace App\Normalizer\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class DeepNormalize
{
    private bool $deepNormalize;

    public function __construct(array $data)
    {
        $this->deepNormalize = (bool)$data['value'];
    }

    public function getDeepNormalize(): bool
    {
        return $this->deepNormalize;
    }
}
