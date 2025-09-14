<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\StationQueue;
use App\Utilities\Types;
use Monolog\LogRecord;
use Psr\Cache\CacheItemPoolInterface;

final class QueueLogCache
{
    private const int CACHE_LIFETIME = 21600;

    private readonly CacheItemPoolInterface $cache;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->cache = CacheNamespace::QueueLog->withNamespace($cacheItemPool);
    }

    /**
     * @param StationQueue $queueRow
     * @return LogRecord[]|null
     */
    public function getLog(StationQueue $queueRow): ?array
    {
        return Types::arrayOrNull(
            $this->cache->getItem($this->getCacheKey($queueRow))->get()
        );
    }

    /**
     * @param StationQueue $queueRow
     * @param LogRecord[]|null $log
     * @return void
     */
    public function setLog(StationQueue $queueRow, ?array $log): void
    {
        if (null !== $log) {
            $log = array_map(
                fn(LogRecord $logRecord) => $logRecord->formatted,
                $log
            );
        }

        $cacheItem = $this->cache->getItem($this->getCacheKey($queueRow));

        $cacheItem->set($log);
        $cacheItem->expiresAfter(self::CACHE_LIFETIME);

        $this->cache->save($cacheItem);
    }

    private function getCacheKey(StationQueue $queueRow): string
    {
        return (string)$queueRow->id;
    }
}
