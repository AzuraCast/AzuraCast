<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use NowPlaying\Enums\AdapterTypes;

final class Shoutcast2 extends AbstractRemote
{
    protected function getAdapterType(): AdapterTypes
    {
        return AdapterTypes::Shoutcast2;
    }
}
