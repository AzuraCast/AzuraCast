<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use DateTimeInterface;

interface TimestampableInterface
{
    public function getTimestamp(): DateTimeInterface;
}
