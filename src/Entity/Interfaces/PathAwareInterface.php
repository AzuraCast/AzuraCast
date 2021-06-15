<?php

namespace App\Entity\Interfaces;

interface PathAwareInterface
{
    public function getPath(): string;

    public function setPath(string $path): void;
}
