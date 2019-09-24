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
use Redis;

/**
 * A wrapper and manager class for accessing assets on the filesystem.
 */
class Filesystem
{
    /** @var CacheItemPoolInterface */
    protected $cachePool;

    /** @var StationFilesystem[] All current interfaces managed by this */
    protected $interfaces = [];

    /**
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = new PrefixedCachePool($cachePool, 'fs|');
    }

    public function getForStation(Entity\Station $station): StationFilesystem
    {
        $station_id = $station->getId();
        if (!isset($this->interfaces[$station_id])) {
            $aliases = [
                'media' => $station->getRadioMediaDir(),
                'albumart' => $station->getRadioAlbumArtDir(),
                'playlists' => $station->getRadioPlaylistsDir(),
                'config' => $station->getRadioConfigDir(),
                'temp' => $station->getRadioTempDir(),
            ];

            $filesystems = [];
            foreach ($aliases as $alias => $localPath) {
                $adapter = new Local($localPath);

                $cachedClient = new Psr6Cache($this->cachePool, $this->normalizeCacheKey($localPath), 3600);
                $filesystems[$alias] = new LeagueFilesystem(new CachedAdapter($adapter, $cachedClient));
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
