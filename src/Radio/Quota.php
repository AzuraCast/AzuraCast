<?php

declare(strict_types=1);

namespace App\Radio;

use Brick\Math;

/**
 * Static utility class for managing quotas.
 */
class Quota
{
    public static function getPercentage(Math\BigInteger $size, Math\BigInteger $total): int
    {
        if (-1 !== $size->compareTo($total)) {
            return 100;
        }

        $size = $size->toBigDecimal();
        return $size->dividedBy($total, 2, Math\RoundingMode::HALF_CEILING)
            ->multipliedBy(100)
            ->toInt();
    }

    public static function getReadableSize(Math\BigInteger $bytes, int $decimals = 1): string
    {
        $bytes_str = (string)$bytes;

        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = (int)floor((strlen($bytes_str) - 1) / 3);

        if (isset($size[$factor])) {
            $byte_divisor = Math\BigInteger::of(1000)->power($factor);
            $size_string = $bytes->toBigDecimal()
                ->dividedBy($byte_divisor, $decimals, Math\RoundingMode::HALF_DOWN);

            return $size_string . ' ' . $size[$factor];
        }

        return $bytes_str;
    }

    public static function convertFromReadableSize(Math\BigInteger|string|null $size): ?Math\BigInteger
    {
        if ($size instanceof Math\BigInteger) {
            return $size;
        }

        if (empty($size)) {
            return null;
        }

        // Remove the non-unit characters from the size.
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size) ?? '';

        // Remove the non-numeric characters from the size.
        $size = preg_replace('/[^0-9\\.]/', '', $size) ?? '';

        if ($unit) {
            // Find the position of the unit in the ordered string which is the power
            // of magnitude to multiply a kilobyte by.

            /** @noinspection StringFragmentMisplacedInspection */
            $byte_power = stripos(
                haystack: 'bkmgtpezy',
                needle:   $unit[0]
            ) ?: 0;
            $byte_multiplier = Math\BigInteger::of(1000)->power($byte_power);

            return Math\BigDecimal::of($size)
                ->multipliedBy($byte_multiplier)
                ->toScale(0, Math\RoundingMode::FLOOR)
                ->toBigInteger();
        }

        return Math\BigInteger::of($size);
    }
}
