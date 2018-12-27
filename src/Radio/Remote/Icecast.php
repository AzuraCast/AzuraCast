<?php
namespace App\Radio\Remote;

use App\Entity;

class Icecast extends AbstractRemote
{
    public function updateNowPlaying(Entity\StationRemote $remote, &$np, $include_clients = false): bool
    {
        return $this->_updateNowPlayingFromAdapter($remote, $np, \NowPlaying\Adapter\Icecast::class, $include_clients);
    }
}
