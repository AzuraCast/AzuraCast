<?php

namespace App\Entity;

interface PathAwareInterface
{
    public function getPath(): string;

    public function setPath(string $path): void;
}
