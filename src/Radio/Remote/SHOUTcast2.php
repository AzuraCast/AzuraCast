<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use NowPlaying\AdapterFactory;

class SHOUTcast2 extends AbstractRemote
{
    protected function getAdapterType(): string
    {
        return AdapterFactory::ADAPTER_SHOUTCAST2;
    }
}
