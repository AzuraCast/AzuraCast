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
        public readonly BigInteger $buffers,
        public readonly BigInteger $cached,
        public readonly BigInteger $sReclaimable,
        public readonly BigInteger $shmem,
        public readonly BigInteger $swapTotal,
        public readonly BigInteger $swapFree,
    ) {
    }

    public static function fromMeminfo(array $meminfo): self
    {
        $memTotal = Quota::convertFromReadableSize($meminfo['MemTotal']) ?? BigInteger::zero();
        $memFree = Quota::convertFromReadableSize($meminfo['MemFree']) ?? BigInteger::zero();
        $buffers = Quota::convertFromReadableSize($meminfo['Buffers']) ?? BigInteger::zero();
        $cached = Quota::convertFromReadableSize($meminfo['Cached']) ?? BigInteger::zero();
        $sReclaimable = Quota::convertFromReadableSize($meminfo['SReclaimable']) ?? BigInteger::zero();
        $shmem = Quota::convertFromReadableSize($meminfo['Shmem']) ?? BigInteger::zero();
        $swapTotal = Quota::convertFromReadableSize($meminfo['SwapTotal']) ?? BigInteger::zero();
        $swapFree = Quota::convertFromReadableSize($meminfo['SwapFree']) ?? BigInteger::zero();

        return new self(
            $memTotal,
            $memFree,
            $buffers,
            $cached,
            $sReclaimable,
            $shmem,
            $swapTotal,
            $swapFree
        );
    }

    public function getUsedMemory(): BigInteger
    {
        $usedDiff = $this->memFree
            ->plus($this->cached)
            ->plus($this->sReclaimable)
            ->minus($this->shmem)
            ->plus($this->buffers);

        return $this->memTotal->isGreaterThanOrEqualTo($usedDiff)
            ? $this->memTotal->minus($usedDiff)
            : $this->memTotal->minus($this->memFree);
    }

    public function getCachedMemory(): BigInteger
    {
        return $this->cached->plus($this->buffers);
    }

    public function getUsedSwap(): BigInteger
    {
        return $this->swapTotal->minus($this->swapFree);
    }
}
