<?php
namespace App\Radio\Remote;

class SHOUTcast2 extends RemoteAbstract
{
    /** @inheritdoc */
    public function updateNowPlaying(&$np, $include_clients = false): bool
    {
        return $this->_updateNowPlayingFromAdapter($np, \NowPlaying\Adapter\SHOUTcast2::class, $include_clients);
    }
}
