<?php
namespace App\Radio;

use App\Entity;
use App\Flysystem\StationFilesystem;
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
    protected CacheItemPoolInterface $cachePool;

    /** @var StationFilesystem[] All current interfaces managed by this */
    protected array $interfaces = [];

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = new PrefixedCachePool($cachePool, 'fs|');
    }

    public function getForStation(Entity\Station $station, bool $cached = true): StationFilesystem
    {
        $station_id = $station->getId();
        if (!isset($this->interfaces[$station_id])) {
            $aliases = [
                'media' => $station->getRadioMediaDir(),
                'albumart' => $station->getRadioAlbumArtDir(),
                'playlists' => $station->getRadioPlaylistsDir(),
                'config' => $station->getRadioConfigDir(),
                'recordings' => $station->getRadioRecordingsDir(),
                'temp' => $station->getRadioTempDir(),
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

            $this->interfaces[$station_id] = new StationFilesystem($filesystems);
        }

        return $this->interfaces[$station_id];
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
