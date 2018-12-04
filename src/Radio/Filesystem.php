<?php
namespace App\Radio;

use App\Entity;
use App\Flysystem\StationFilesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\PhpRedis;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Filesystem as LeagueFilesystem;

/**
 * A wrapper and manager class for accessing assets on the filesystem.
 */
class Filesystem
{
    /** @var \Redis */
    protected $redis;

    /** @var StationFilesystem[] All current interfaces managed by this  */
    protected $interfaces = [];

    /**
     * @param \Redis $redis
     *
     * @see \App\Provider\RadioProvider
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function getForStation(Entity\Station $station): StationFilesystem
    {
        $station_id = $station->getId();
        if (!isset($this->interfaces[$station_id])) {
            $this->interfaces[$station_id] = new StationFilesystem([
                'media'         => $this->_getLocalInterface($station->getRadioMediaDir()),
                'albumart'      => $this->_getLocalInterface($station->getRadioAlbumArtDir()),
                'playlists'     => $this->_getLocalInterface($station->getRadioPlaylistsDir()),
                'config'        => $this->_getLocalInterface($station->getRadioConfigDir()),
                'temp'          => $this->_getLocalInterface($station->getRadioTempDir()),
            ]);
        }

        return $this->interfaces[$station_id];
    }

    protected function _getLocalInterface($local_path): FilesystemInterface
    {
        $adapter = new Local($local_path);
        return new LeagueFilesystem($this->_getCachedAdapter($adapter));
    }

    protected function _getCachedAdapter(AdapterInterface $adapter): CachedAdapter
    {
        $cached_client = new PhpRedis($this->redis, 'flysystem', 43200);
        return new CachedAdapter($adapter, $cached_client);
    }
}
