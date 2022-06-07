<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Entity\Station;

interface StationAwareInterface
{
    public function getStation(): ?Station;
}
