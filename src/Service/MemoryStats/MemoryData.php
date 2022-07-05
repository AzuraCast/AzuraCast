<?php

declare(strict_types=1);

namespace App\Service\MemoryStats;

use App\Radio\Quota;
use Brick\Math\BigInteger;

final class MemoryData
{
    public function __construct(
        public readonly BigInteger $memTotal,
        public readonly BigInteger $memFree,
        public readonly BigInteger $cached,
        public readonly BigInteger $swapTotal,
        public readonly BigInteger $swapFree,
    ) {
    }

    public static function fromMeminfo(array $meminfo): self
    {
        $memTotal = Quota::convertFromReadableSize($meminfo['MemTotal']) ?? BigInteger::zero();
        $memFree = Quota::convertFromReadableSize($meminfo['MemFree']) ?? BigInteger::zero();
        $cached = Quota::convertFromReadableSize($meminfo['Cached']) ?? BigInteger::zero();
        $swapTotal = Quota::convertFromReadableSize($meminfo['SwapTotal']) ?? BigInteger::zero();
        $swapFree = Quota::convertFromReadableSize($meminfo['SwapFree']) ?? BigInteger::zero();

        return new self(
            $memTotal,
            $memFree,
            $cached,
            $swapTotal,
            $swapFree
        );
    }

    public function getUsedMemory(): BigInteger
    {
        return $this->memTotal
            ->minus($this->memFree)
            ->minus($this->cached);
    }

    public function getUsedSwap(): BigInteger
    {
        return $this->swapTotal->minus($this->swapFree);
    }
}
