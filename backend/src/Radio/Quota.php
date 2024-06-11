<?php

declare(strict_types=1);

namespace App\Radio;

use Brick\Math;

/**
 * Static utility class for managing quotas.
 */
final class Quota
{
    public static function getPercentage(Math\BigInteger $size, Math\BigInteger $total): int
    {
        if (-1 !== $size->compareTo($total)) {
            return 100;
        }

        return $size->toBigDecimal()
            ->dividedBy($total, 2, Math\RoundingMode::HALF_CEILING)
            ->multipliedBy(100)
            ->toInt();
    }

    public static function getReadableSize(Math\BigInteger $bytes, int $decimals = 1): string
    {
        $bytesStr = (string)$bytes;

        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = (int)floor((strlen($bytesStr) - 1) / 3);

        if (isset($size[$factor])) {
            $byteDivisor = Math\BigInteger::of(1024)->power($factor);
            $sizeString = $bytes->toBigDecimal()
                ->dividedBy($byteDivisor, $decimals, Math\RoundingMode::HALF_DOWN);

            return $sizeString . ' ' . $size[$factor];
        }

        return $bytesStr;
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
        $size = preg_replace('/[^\d.]/', '', $size) ?? '';

        if ($unit) {
            // Find the position of the unit in the ordered string which is the power
            // of magnitude to multiply a kilobyte by.

            /** @noinspection StringFragmentMisplacedInspection */
            $bytePower = stripos(
                haystack: 'bkmgtpezy',
                needle: $unit[0]
            ) ?: 0;
            $byteMultiplier = Math\BigInteger::of(1024)->power($bytePower);

            return Math\BigDecimal::of($size)
                ->multipliedBy($byteMultiplier)
                ->toScale(0, Math\RoundingMode::FLOOR)
                ->toBigInteger();
        }

        return Math\BigInteger::of($size);
    }
}
