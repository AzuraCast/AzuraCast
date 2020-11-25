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

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = new PrefixedCachePool($cachePool, 'fs|');
    }

    public function getForStation(Entity\Station $station, bool $cached = true): StationFilesystemGroup
    {
        $aliases = [
            self::PREFIX_MEDIA,
            self::PREFIX_PLAYLISTS,
            self::PREFIX_CONFIG,
            self::PREFIX_RECORDINGS,
            self::PREFIX_TEMP,
        ];

        $filesystems = [];
        foreach ($aliases as $alias) {
            $filesystems[$alias] = $this->getPrefixedAdapterForStation($station, $alias, $cached);
        }

        return new StationFilesystemGroup($filesystems);
    }

    public function getPrefixedAdapterForStation(
        Entity\Station $station,
        string $prefix,
        bool $cached = true
    ): Filesystem {
        $isCachable = false;

        switch ($prefix) {
            case self::PREFIX_MEDIA:
                $adapter = $station->getRadioMediaDirAdapter();
                $isCachable = true;
                break;

            case self::PREFIX_RECORDINGS:
                $adapter = $station->getRadioRecordingsDirAdapter();
                $isCachable = true;
                break;

            case self::PREFIX_PLAYLISTS:
                $adapter = $station->getRadioPlaylistsDirAdapter();
                break;

            case self::PREFIX_CONFIG:
                $adapter = $station->getRadioConfigDirAdapter();
                break;

            case self::PREFIX_TEMP:
                $adapter = $station->getRadioTempDirAdapter();
                break;

            default:
                throw new \InvalidArgumentException(sprintf("Invalid adapter: %s", $prefix));
        }

        return $this->getFilesystemForAdapter($adapter, $isCachable && $cached);
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

    public static function applyPrefix(string $prefix, string $path): string
    {
        $path = ltrim(self::stripPrefix($path), '/');

        return $prefix . '://' . $path;
    }

    public static function stripPrefix(string $path): string
    {
        if (strpos($path, '://') !== false) {
            [, $path] = explode('://', $path, 2);
        }

        return $path;
    }
}
