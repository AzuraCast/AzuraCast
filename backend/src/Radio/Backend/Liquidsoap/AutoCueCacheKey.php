<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

final readonly class AutoCueCacheKey
{
    public function __construct(
        public int $mediaId,
        public int $mtime
    ) {
    }

    public static function tryFromString(string $cacheKey): ?self
    {
        if (preg_match('/^(\d+)_(\d+)$/', $cacheKey, $matches) !== 1) {
            return null;
        }

        return new self((int) $matches[1], (int) $matches[2]);
    }

    public function __toString(): string
    {
        return "{$this->mediaId}_{$this->mtime}";
    }
}
