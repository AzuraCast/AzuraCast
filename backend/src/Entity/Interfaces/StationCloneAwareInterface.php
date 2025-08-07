<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Entity\Station;

interface StationCloneAwareInterface
{
    public function setStation(Station $station): void;
}
