<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface IdentifiableEntityInterface
{
    public int|string $id {
        get;
    }
}
