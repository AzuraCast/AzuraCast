<?php

declare(strict_types=1);

namespace App\Cache;

use App\Sync\Task\AbstractTask;
use App\Utilities\Types;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;

final class SyncStatusCache
{
    private const int CACHE_LIFETIME = 86400;

    private readonly CacheItemPoolInterface $cache;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->cache = CacheNamespace::SyncStatus->withNamespace($cacheItemPool);
    }

    /**
     * @param class-string<AbstractTask> $taskName
     * @return void
     */
    public function markTaskAsRun(string $taskName): void
    {
        $cacheKey = $this->getTaskCacheKey($taskName);

        $cacheItem = $this->cache->getItem($cacheKey)
            ->set(time())
            ->expiresAfter(self::CACHE_LIFETIME);

        $this->cache->save($cacheItem);
    }

    /**
     * @param class-string<AbstractTask> $taskName
     * @return int|null
     */
    public function getTaskLastRun(string $taskName): ?int
    {
        $cacheKey = $this->getTaskCacheKey($taskName);

        return Types::intOrNull(
            $this->cache->getItem($cacheKey)->get()
        );
    }

    /**
     * @param class-string<AbstractTask> $taskName
     * @return string
     */
    private function getTaskCacheKey(string $taskName): string
    {
        return urlencode(
            new ReflectionClass($taskName)->getShortName()
        );
    }
}
