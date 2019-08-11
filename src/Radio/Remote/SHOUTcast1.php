<?php
namespace App\Radio\Remote;

use App\Entity;

class SHOUTcast1 extends AbstractRemote
{
    /** @inheritDoc */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        return $this->_getRemoteUrl($remote, '/;stream.nsv');
    }

    /**
     * @inheritDoc
     */
    public function updateNowPlaying(Entity\StationRemote $remote, $np_aggregate, bool $include_clients = false): array
    {
        return $this->_updateNowPlayingFromAdapter(
            $remote,
            $np_aggregate,
            \NowPlaying\Adapter\SHOUTcast1::class,
            $include_clients
        );
    }
}
