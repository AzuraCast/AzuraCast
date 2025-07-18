<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use App\Utilities\Types;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @phpstan-type LookupRow array{
 *     short_name: string,
 *     is_public: bool,
 *     updated_at: int
 * }
 */
final class NowPlayingCache
{
    private const int NOWPLAYING_CACHE_TTL = 180;

    private readonly CacheItemPoolInterface $cache;

    public function __construct(
        CacheItemPoolInterface $cache
    ) {
        $this->cache = CacheNamespace::NowPlaying->withNamespace($cache);
    }

    public function setForStation(
        Station $station,
        ?NowPlaying $nowPlaying
    ): void {
        $this->populateLookupCache($station);

        $stationCacheItem = $this->getStationCache($station->short_name);

        $stationCacheItem->set($nowPlaying);
        $stationCacheItem->expiresAfter(self::NOWPLAYING_CACHE_TTL);
        $this->cache->saveDeferred($stationCacheItem);

        $this->cache->commit();
    }

    public function getForStation(string|Station $station): ?NowPlaying
    {
        if ($station instanceof Station) {
            $station = $station->short_name;
        }

        $stationCacheItem = $this->getStationCache($station);

        if (!$stationCacheItem->isHit()) {
            return null;
        }

        $np = $stationCacheItem->get();
        assert($np instanceof NowPlaying);
        return $np;
    }

    /**
     * @param bool $publicOnly
     * @return NowPlaying[]
     */
    public function getForAllStations(bool $publicOnly = false): array
    {
        $lookupCacheItem = $this->getLookupCache();
        if (!$lookupCacheItem->isHit()) {
            return [];
        }

        $np = [];

        /** @var LookupRow[] $lookupCache */
        $lookupCache = (array)$lookupCacheItem->get();

        foreach ($lookupCache as $stationInfo) {
            if ($publicOnly && !$stationInfo['is_public']) {
                continue;
            }

            $npRowItem = $this->getStationCache($stationInfo['short_name']);
            $npRow = $npRowItem->isHit()
                ? $npRowItem->get()
                : null;

            if ($npRow instanceof NowPlaying) {
                $np[] = $npRow;
            }
        }

        return $np;
    }

    /**
     * @return array<int, LookupRow>
     */
    public function getLookup(): array
    {
        $lookupCacheItem = $this->getLookupCache();
        return $lookupCacheItem->isHit()
            ? Types::array($lookupCacheItem->get())
            : [];
    }

    /**
     * Given a station, remove it from the lookup cache so that the NowPlaying task runner immediately runs its Now
     * Playing task next. This encourages timely updates when songs change without interfering with concurrency of the
     * NowPlaying sync command.
     *
     * @param Station $station
     * @return void
     */
    public function forceUpdate(Station $station): void
    {
        $this->populateLookupCache($station, 0);
        $this->cache->commit();
    }

    private function getLookupCache(): CacheItemInterface
    {
        return $this->cache->getItem('lookup');
    }

    private function populateLookupCache(
        Station $station,
        ?int $updated = null
    ): void {
        $lookupCacheItem = $this->getLookupCache();

        $lookupCache = $lookupCacheItem->isHit()
            ? Types::array($lookupCacheItem->get())
            : [];

        $lookupCache[$station->id] = [
            'short_name' => $station->short_name,
            'is_public' => $station->enable_public_page,
            'updated_at' => $updated ?? time(),
        ];

        $lookupCacheItem->set($lookupCache);
        $lookupCacheItem->expiresAfter(self::NOWPLAYING_CACHE_TTL);
        $this->cache->saveDeferred($lookupCacheItem);
    }

    private function getStationCache(string $identifier): CacheItemInterface
    {
        if (is_numeric($identifier)) {
            $lookupCache = $this->getLookup();

            $identifier = Types::int($identifier);
            if (isset($lookupCache[$identifier])) {
                $identifier = $lookupCache[$identifier]['short_name'];
            }
        }

        return $this->cache->getItem(urlencode('station_' . $identifier));
    }
}
