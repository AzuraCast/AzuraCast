<?php
namespace App\Flysystem;

use App\Entity;
use Cache\Prefixed\PrefixedCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Psr6Cache;
use League\Flysystem\Filesystem as LeagueFilesystem;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A wrapper and manager class for accessing assets on the filesystem.
 */
class Filesystem
{
    public const PREFIX_MEDIA = 'media';
    public const PREFIX_ALBUM_ART = 'albumart';
    public const PREFIX_WAVEFORMS = 'waveforms';
    public const PREFIX_PLAYLISTS = 'playlists';
    public const PREFIX_CONFIG = 'config';
    public const PREFIX_RECORDINGS = 'recordings';
    public const PREFIX_TEMP = 'temp';

    protected CacheItemPoolInterface $cachePool;

    /** @var StationFilesystem[] All current interfaces managed by this instance. */
    protected array $interfaces = [];

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = new PrefixedCachePool($cachePool, 'fs|');
    }

    public function getForStation(Entity\Station $station, bool $cached = true): StationFilesystem
    {
        $stationId = $station->getId();
        $interfaceKey = ($cached)
            ? $stationId . '_cached'
            : $stationId . '_uncached';

        if (!isset($this->interfaces[$interfaceKey])) {
            $aliases = [
                self::PREFIX_MEDIA => $station->getRadioMediaDir(),
                self::PREFIX_ALBUM_ART => $station->getRadioAlbumArtDir(),
                self::PREFIX_WAVEFORMS => $station->getRadioWaveformsDir(),
                self::PREFIX_PLAYLISTS => $station->getRadioPlaylistsDir(),
                self::PREFIX_CONFIG => $station->getRadioConfigDir(),
                self::PREFIX_RECORDINGS => $station->getRadioRecordingsDir(),
                self::PREFIX_TEMP => $station->getRadioTempDir(),
            ];

            $filesystems = [];
            foreach ($aliases as $alias => $localPath) {
                $adapter = new Local($localPath);

                if ($cached) {
                    $cachedClient = new Psr6Cache($this->cachePool, $this->normalizeCacheKey($localPath), 3600);
                    $adapter = new CachedAdapter($adapter, $cachedClient);
                }

                $filesystems[$alias] = new LeagueFilesystem($adapter);
            }

            $this->interfaces[$interfaceKey] = new StationFilesystem($filesystems);
        }

        return $this->interfaces[$interfaceKey];
    }

    protected function normalizeCacheKey(string $path): string
    {
        $path = ltrim($path, '/');

        if (preg_match('|[\{\}\(\)/\\\@\:]|', $path)) {
            return preg_replace('|[\{\}\(\)/\\\@\:]|', '_', $path);
        }

        return $path;
    }
}
