<?php

declare(strict_types=1);

namespace App\Radio\Remote;

use App\Entity;

class AdapterProxy
{
    public function __construct(
        protected AbstractRemote $adapter,
        protected Entity\StationRemote $remote
    ) {
    }

    public function getAdapter(): AbstractRemote
    {
        return $this->adapter;
    }

    public function getRemote(): Entity\StationRemote
    {
        return $this->remote;
    }
}
