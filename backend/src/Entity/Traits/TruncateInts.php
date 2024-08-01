<?php

declare(strict_types=1);

namespace App\Entity\Traits;

trait TruncateInts
{
    protected function truncateSmallInt(int $int, bool $unsigned = false): int
    {
        return $this->truncateIntToLimit(32767, 65535, $unsigned, $int);
    }

    protected function truncateNullableSmallInt(?int $int = null, bool $unsigned = false): ?int
    {
        if (null === $int) {
            return null;
        }

        return $this->truncateSmallInt($int, $unsigned);
    }

    protected function truncateTinyInt(int $int, bool $unsigned = false): int
    {
        return $this->truncateIntToLimit(127, 255, $unsigned, $int);
    }

    protected function truncateNullableTinyInt(?int $int = null, bool $unsigned = false): ?int
    {
        if (null === $int) {
            return null;
        }

        return $this->truncateTinyInt($int, $unsigned);
    }

    protected function truncateInt(int $int, bool $unsigned = false): int
    {
        return $this->truncateIntToLimit(2147483647, 4294967295, $unsigned, $int);
    }

    protected function truncateNullableInt(?int $int = null, bool $unsigned = false): ?int
    {
        if (null === $int) {
            return null;
        }

        return $this->truncateInt($int, $unsigned);
    }

    protected function truncateIntToLimit(
        int $signedLimit,
        int $unsignedLimit,
        bool $unsigned,
        int $int
    ): int {
        $lowerLimit = $unsigned ? 0 : 0 - $signedLimit;
        $upperLimit = $unsigned ? $unsignedLimit : $signedLimit;

        if ($int < $lowerLimit) {
            return $lowerLimit;
        }
        if ($int > $upperLimit) {
            return $upperLimit;
        }

        return $int;
    }
}
