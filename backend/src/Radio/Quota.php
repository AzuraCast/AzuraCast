<?php

declare(strict_types=1);

namespace App\Radio;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * Static utility class for managing quotas.
 */
final class Quota
{
    public static function getPercentage(BigInteger $size, BigInteger $total): int
    {
        if (-1 !== $size->compareTo($total)) {
            return 100;
        }

        return $size->toBigDecimal()
            ->dividedBy($total, 2, RoundingMode::Ceiling)
            ->multipliedBy(100)
            ->toInt();
    }

    /**
     * @param int<0, max> $decimals
     */
    public static function getReadableSize(BigInteger $bytes, int $decimals = 1): string
    {
        $bytesStr = (string)$bytes;

        $size = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = (int)floor((strlen($bytesStr) - 1) / 3);

        if (isset($size[$factor])) {
            $byteDivisor = BigInteger::of(1024)->power($factor);
            $sizeString = $bytes->toBigDecimal()
                ->dividedBy($byteDivisor, $decimals, RoundingMode::Down);

            return $sizeString . ' ' . $size[$factor];
        }

        return $bytesStr;
    }

    public static function convertFromReadableSize(BigInteger|string|null $size): ?BigInteger
    {
        if ($size instanceof BigInteger) {
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
            $byteMultiplier = BigInteger::of(1024)->power($bytePower);

            return BigDecimal::of($size)
                ->multipliedBy($byteMultiplier)
                ->toScale(0, RoundingMode::Floor)
                ->toBigInteger();
        }

        return BigInteger::of($size);
    }
}
