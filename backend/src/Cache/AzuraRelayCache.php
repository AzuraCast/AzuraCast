<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\Relay;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class AzuraRelayCache
{
    private const int CACHE_TTL = 600;

    private readonly CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = CacheNamespace::AzuraRelay->withNamespace($cache);
    }

    public function setForRelay(
        Relay $relay,
        array $np
    ): void {
        $cacheItem = $this->getCacheItem($relay);

        $cacheItem->set($np);
        $cacheItem->expiresAfter(self::CACHE_TTL);

        $this->cache->save($cacheItem);
    }

    public function getForRelay(Relay $relay): array
    {
        $cacheItem = $this->getCacheItem($relay);
        return $cacheItem->isHit()
            ? (array)$cacheItem->get()
            : [];
    }

    private function getCacheItem(Relay $relay): CacheItemInterface
    {
        return $this->cache->getItem('relay_' . $relay->id);
    }
}
