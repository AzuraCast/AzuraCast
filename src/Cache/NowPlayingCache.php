<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class NowPlayingCache
{
    private const NOWPLAYING_CACHE_TTL = 180;

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public function setForStation(
        Station $station,
        ?NowPlaying $nowPlaying
    ): void {
        $lookupCacheItem = $this->getLookupCache();

        $lookupCache = $lookupCacheItem->isHit()
            ? (array)$lookupCacheItem->get()
            : [];

        $lookupCache[$station->getIdRequired()] = [
            'short_name' => $station->getShortName(),
            'is_public' => $station->getEnablePublicPage(),
            'updated_at' => time(),
        ];

        $lookupCacheItem->set($lookupCache);
        $lookupCacheItem->expiresAfter(self::NOWPLAYING_CACHE_TTL);
        $this->cache->saveDeferred($lookupCacheItem);

        $stationCacheItem = $this->getStationCache($station->getShortName());

        $stationCacheItem->set($nowPlaying);
        $stationCacheItem->expiresAfter(self::NOWPLAYING_CACHE_TTL);
        $this->cache->saveDeferred($stationCacheItem);

        $this->cache->commit();
    }

    public function getForStation(string|Station $station): ?NowPlaying
    {
        if ($station instanceof Station) {
            $station = $station->getShortName();
        }

        $stationCacheItem = $this->getStationCache($station);

        return ($stationCacheItem->isHit())
            ? $stationCacheItem->get()
            : null;
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

    public function getLookup(): array
    {
        $lookupCacheItem = $this->getLookupCache();
        return $lookupCacheItem->isHit()
            ? (array)$lookupCacheItem->get()
            : [];
    }

    private function getLookupCache(): CacheItemInterface
    {
        return $this->cache->getItem(
            'now_playing.lookup'
        );
    }

    private function getStationCache(string $identifier): CacheItemInterface
    {
        if (is_numeric($identifier)) {
            $lookupCacheItem = $this->getLookupCache();
            $lookupCache = $lookupCacheItem->isHit()
                ? (array)$lookupCacheItem->get()
                : [];

            $identifier = (int)$identifier;
            if (isset($lookupCache[$identifier])) {
                $identifier = $lookupCache[$identifier]['short_name'];
            }
        }

        return $this->cache->getItem(
            'now_playing.station_' . $identifier
        );
    }
}
