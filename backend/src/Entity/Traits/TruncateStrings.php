<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Utilities\Types;

trait TruncateStrings
{
    protected function truncateNullableString(
        ?string $string = null,
        int $length = 255,
        bool $countEmptyAsNull = false
    ): ?string {
        if ($countEmptyAsNull) {
            $string = Types::stringOrNull($string, true);
        }

        if ($string === null) {
            return null;
        }

        return $this->truncateString($string, $length);
    }

    protected function truncateString(string $string, int $length = 255): string
    {
        return mb_substr($string, 0, $length, 'UTF-8');
    }
}
