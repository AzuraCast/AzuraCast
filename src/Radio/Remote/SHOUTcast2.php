<?php
namespace App\Radio\Remote;

use App\Entity;

class SHOUTcast2 extends AbstractRemote
{
    /** @inheritdoc */
    public function updateNowPlaying(Entity\StationRemote $remote, &$np, $include_clients = false): bool
    {
        return $this->_updateNowPlayingFromAdapter($remote, $np, \NowPlaying\Adapter\SHOUTcast2::class, $include_clients);
    }
}
