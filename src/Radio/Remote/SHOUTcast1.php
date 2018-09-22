<?php
namespace App\Radio\Remote;

use App\Entity;

class SHOUTcast1 extends RemoteAbstract
{
    /** @inheritdoc */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        return $this->_getRemoteUrl($remote, '/;stream.nsv');
    }

    /** @inheritdoc */
    public function updateNowPlaying(Entity\StationRemote $remote, &$np, $include_clients = false): bool
    {
        return $this->_updateNowPlayingFromAdapter($remote, $np, \NowPlaying\Adapter\SHOUTcast1::class, $include_clients);
    }
}
