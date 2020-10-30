<?php

namespace App\Flysystem;

use App\Entity;
use Cache\Prefixed\PrefixedCachePool;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Psr6Cache;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A wrapper and manager class for accessing assets on the filesystem.
 */
class FilesystemManager
{
    public const PREFIX_MEDIA = 'media';
    public const PREFIX_PLAYLISTS = 'playlists';
    public const PREFIX_CONFIG = 'config';
    public const PREFIX_RECORDINGS = 'recordings';
    public const PREFIX_TEMP = 'temp';

    protected CacheItemPoolInterface $cachePool;

    /** @var StationFilesystemGroup[] All current interfaces managed by this instance. */
    protected array $interfaces = [];

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = new PrefixedCachePool($cachePool, 'fs|');
    }

    public function getForStation(Entity\Station $station, bool $cached = true): StationFilesystemGroup
    {
        $stationId = $station->getId();
        $interfaceKey = ($cached)
            ? $stationId . '_cached'
            : $stationId . '_uncached';

        if (!isset($this->interfaces[$interfaceKey])) {
            /** @var AdapterInterface[] $aliases */
            $aliases = [
                self::PREFIX_MEDIA => $station->getRadioMediaDirAdapter(),
                self::PREFIX_PLAYLISTS => $station->getRadioPlaylistsDirAdapter(),
                self::PREFIX_CONFIG => $station->getRadioConfigDirAdapter(),
                self::PREFIX_RECORDINGS => $station->getRadioRecordingsDirAdapter(),
                self::PREFIX_TEMP => $station->getRadioTempDirAdapter(),
            ];

            $filesystems = [];
            foreach ($aliases as $alias => $adapter) {
                $filesystems[$alias] = $this->getFilesystemForAdapter($adapter, $cached);
            }

            $this->interfaces[$interfaceKey] = new StationFilesystemGroup($filesystems);
        }

        return $this->interfaces[$interfaceKey];
    }

    public function getFilesystemForAdapter(AdapterInterface $adapter, bool $cached = false): Filesystem
    {
        if ($cached) {
            $cachedClient = new Psr6Cache($this->cachePool, $this->getCacheKey($adapter), 3600);
            $adapter = new CachedAdapter($adapter, $cachedClient);
        }

        return new Filesystem($adapter);
    }

    public function flushCacheForAdapter(AdapterInterface $adapter, bool $inMemoryOnly = false): void
    {
        $fs = $this->getFilesystemForAdapter($adapter, true);
        $fs->clearCache($inMemoryOnly);
    }

    protected function getCacheKey(AdapterInterface $adapter): string
    {
        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if ($adapter instanceof AwsS3Adapter) {
            $s3Client = $adapter->getClient();
            $bucket = $adapter->getBucket();

            $objectUrl = $s3Client->getObjectUrl($bucket, $adapter->applyPathPrefix('/cache'));
            return $this->filterCacheKey($objectUrl);
        }
        if ($adapter instanceof AbstractAdapter) {
            return $this->filterCacheKey(ltrim($adapter->getPathPrefix(), '/'));
        }

        throw new \InvalidArgumentException('Adapter does not have a cache key.');
    }

    protected function filterCacheKey(string $cacheKey): string
    {
        if (preg_match('|[\{\}\(\)/\\\@\:]|', $cacheKey)) {
            return preg_replace('|[\{\}\(\)/\\\@\:]|', '_', $cacheKey);
        }
        return $cacheKey;
    }
}
