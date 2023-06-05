<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use NowPlaying\Enums\AdapterTypes;

final class Icecast extends AbstractRemote
{
    protected function getAdapterType(): AdapterTypes
    {
        return AdapterTypes::Icecast;
    }
}
