<?php
namespace App\Radio\Remote;

use App\Entity;

class SHOUTcast2 extends AbstractRemote
{
    /**
     * @inheritDoc
     */
    public function updateNowPlaying(Entity\StationRemote $remote, $np_aggregate, bool $include_clients = false): array
    {
        return $this->_updateNowPlayingFromAdapter(
            $remote,
            $np_aggregate,
            \NowPlaying\Adapter\SHOUTcast2::class,
            $include_clients
        );
    }
}
