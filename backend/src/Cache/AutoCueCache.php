<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\StationMedia;
use App\Utilities\Types;
use Psr\Cache\CacheItemPoolInterface;

final class AutoCueCache
{
    private const int CACHE_LIFETIME = 86400;

    private readonly CacheItemPoolInterface $cache;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->cache = CacheNamespace::AutoCue->withNamespace($cacheItemPool);
    }

    public function getCacheKey(StationMedia $media): string
    {
        return $media->unique_id . '_' . $media->mtime;
    }

    public function setForCacheKey(
        string $cacheKey,
        ?array $value
    ): void {
        if ($value === null) {
            return;
        }

        $cacheItem = $this->cache->getItem($cacheKey);

        $cacheItem->set($value);
        $cacheItem->expiresAfter(self::CACHE_LIFETIME);

        $this->cache->save($cacheItem);
    }

    public function getForCacheKey(string $cacheKey): ?array
    {
        return Types::arrayOrNull(
            $this->cache->getItem($cacheKey)->get()
        );
    }
}
