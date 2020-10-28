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
    public const PREFIX_ALBUM_ART = 'albumart';
    public const PREFIX_WAVEFORMS = 'waveforms';
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
                self::PREFIX_ALBUM_ART => $station->getRadioAlbumArtDirAdapter(),
                self::PREFIX_WAVEFORMS => $station->getRadioWaveformsDirAdapter(),
                self::PREFIX_PLAYLISTS => $station->getRadioPlaylistsDirAdapter(),
                self::PREFIX_CONFIG => $station->getRadioConfigDirAdapter(),
                self::PREFIX_RECORDINGS => $station->getRadioRecordingsDirAdapter(),
                self::PREFIX_TEMP => $station->getRadioTempDirAdapter(),
            ];

            $filesystems = [];
            foreach ($aliases as $alias => $adapter) {
                if ($cached) {
                    $cachedClient = new Psr6Cache($this->cachePool, $this->getCacheKey($adapter), 3600);
                    $adapter = new CachedAdapter($adapter, $cachedClient);
                }

                $filesystems[$alias] = new Filesystem($adapter);
            }

            $this->interfaces[$interfaceKey] = new StationFilesystemGroup($filesystems);
        }

        return $this->interfaces[$interfaceKey];
    }

    public function clearCacheForAdapter(AdapterInterface $adapter): bool
    {
        $cacheKey = $this->getCacheKey($adapter);
        return $this->cachePool->deleteItem($cacheKey);
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
