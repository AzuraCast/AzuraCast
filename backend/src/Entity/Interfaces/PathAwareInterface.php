<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface PathAwareInterface
{
    public string $path {
        get;
        set;
    }
}
