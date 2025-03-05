<?php

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

trait LoadFromParentObject
{
    /**
     * @param object|array<mixed> $parent
     */
    public static function fromParent(array|object $parent): self
    {
        if (is_object($parent)) {
            $parent = get_object_vars($parent);
        }

        return new PropertyNormalizer()->denormalize(
            $parent,
            self::class
        );
    }
}
