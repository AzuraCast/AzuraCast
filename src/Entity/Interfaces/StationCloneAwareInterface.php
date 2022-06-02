<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Entity;

interface StationCloneAwareInterface extends StationAwareInterface
{
    public function setStation(Entity\Station $station): void;
}
