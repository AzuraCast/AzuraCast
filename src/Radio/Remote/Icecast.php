<?php

namespace App\Radio\Remote;

use NowPlaying\AdapterFactory;

class Icecast extends AbstractRemote
{
    protected function getAdapterType(): string
    {
        return AdapterFactory::ADAPTER_ICECAST;
    }
}
