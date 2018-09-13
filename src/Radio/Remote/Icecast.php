<?php
namespace App\Radio\Remote;

class Icecast extends RemoteAbstract
{
    public function updateNowPlaying(&$np, $include_clients = false): bool
    {
        return $this->_updateNowPlayingFromAdapter($np, \NowPlaying\Adapter\Icecast::class, $include_clients);
    }
}
