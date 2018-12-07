<?php
namespace App\Radio;

use App\Entity;
use App\Flysystem\StationFilesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\PhpRedis;
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
            $aliases = [
                'media'     => $station->getRadioMediaDir(),
                'albumart'  => $station->getRadioAlbumArtDir(),
                'playlists' => $station->getRadioPlaylistsDir(),
                'config'    => $station->getRadioConfigDir(),
                'temp'      => $station->getRadioTempDir(),
            ];

            $filesystems = [];
            foreach($aliases as $alias => $local_path) {
                $adapter = new Local($local_path);

                $fs_location_key = 'fs_'.substr(md5($local_path), 0, 10);

                $cached_client = new PhpRedis($this->redis, $fs_location_key, 3600);
                $filesystems[$alias] = new LeagueFilesystem(new CachedAdapter($adapter, $cached_client));
            }

            $this->interfaces[$station_id] = new StationFilesystem($filesystems);
        }

        return $this->interfaces[$station_id];
    }
}
