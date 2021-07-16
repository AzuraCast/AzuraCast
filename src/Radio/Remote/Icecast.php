<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use NowPlaying\AdapterFactory;

class Icecast extends AbstractRemote
{
    protected function getAdapterType(): string
    {
        return AdapterFactory::ADAPTER_ICECAST;
    }
}
