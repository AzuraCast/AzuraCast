<?php

namespace App\Entity\Traits;

trait TruncateInts
{
    /**
     * @param int|null $int
     * @param bool $unsigned
     *
     * @return int|null
     */
    protected function truncateSmallInt(?int $int = null, bool $unsigned = false): ?int
    {
        return $this->truncateIntToLimit(32767, 65535, $unsigned, $int);
    }

    /**
     * @param int $signed_limit
     * @param int $unsigned_limit
     * @param bool $unsigned
     * @param int|null $int
     *
     * @return int|null
     */
    protected function truncateIntToLimit(
        int $signed_limit,
        int $unsigned_limit,
        bool $unsigned,
        ?int $int = null
    ): ?int {
        if (null === $int) {
            return null;
        }

        $lower_limit = $unsigned ? 0 : 0 - $signed_limit;
        $upper_limit = $unsigned ? $unsigned_limit : $signed_limit;

        if ($int < $lower_limit) {
            return $lower_limit;
        }
        if ($int > $upper_limit) {
            return $upper_limit;
        }

        return $int;
    }

    /**
     * @param int|null $int
     * @param bool $unsigned
     *
     * @return int|null
     */
    protected function truncateTinyInt(?int $int = null, bool $unsigned = false): ?int
    {
        return $this->truncateIntToLimit(127, 255, $unsigned, $int);
    }
}
