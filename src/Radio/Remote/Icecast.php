<?php
namespace App\Radio\Remote;

use App\Entity;

class Icecast extends AbstractRemote
{
    /**
     * @inheritDoc
     */
    public function updateNowPlaying(Entity\StationRemote $remote, $np_aggregate, bool $include_clients = false): array
    {
        return $this->_updateNowPlayingFromAdapter(
            $remote,
            $np_aggregate,
            \NowPlaying\Adapter\Icecast::class,
            $include_clients
        );
    }
}
