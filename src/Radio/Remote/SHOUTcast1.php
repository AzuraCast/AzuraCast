<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Entity;
use NowPlaying\AdapterFactory;

class SHOUTcast1 extends AbstractRemote
{
    protected function getAdapterType(): string
    {
        return AdapterFactory::ADAPTER_SHOUTCAST1;
    }

    /** @inheritDoc */
    public function getPublicUrl(Entity\StationRemote $remote): string
    {
        return $this->getRemoteUrl($remote, '/;stream.nsv');
    }
}
