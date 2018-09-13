<?php
namespace App\Radio\Remote;

class SHOUTcast1 extends RemoteAbstract
{
    /** @inheritdoc */
    public function getPublicUrl(): string
    {
        return $this->_getRemoteUrl('/;stream.nsv');
    }

    /** @inheritdoc */
    public function updateNowPlaying(&$np, $include_clients = false): bool
    {
        return $this->_updateNowPlayingFromAdapter($np, \NowPlaying\Adapter\SHOUTcast1::class, $include_clients);
    }
}
