<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface IdentifiableEntityInterface
{
    public function getId(): null|int|string;

    public function getIdRequired(): int|string;
}
