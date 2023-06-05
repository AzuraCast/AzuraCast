<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Entity;
use NowPlaying\Enums\AdapterTypes;

final class Shoutcast1 extends AbstractRemote
{
    protected function getAdapterType(): AdapterTypes
    {
        return AdapterTypes::Shoutcast1;
    }

    /** @inheritDoc */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        return $this->getRemoteUrl($remote, '/;stream.nsv');
    }
}
