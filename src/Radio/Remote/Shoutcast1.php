<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use NowPlaying\Enums\AdapterTypes;
use App\Entity\StationRemote;

final class Shoutcast1 extends AbstractRemote
{
    protected function getAdapterType(): AdapterTypes
    {
        return AdapterTypes::Shoutcast1;
    }

    /** @inheritDoc */
    public function getPublicUrl(StationRemote $remote): string
    {
        return $this->getRemoteUrl($remote, '/;stream.nsv');
    }
}
