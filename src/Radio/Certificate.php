<?php

declare(strict_types=1);

namespace App\Radio;

class Certificate
{
    public function __construct(
        protected string $keyPath,
        protected string $certPath
    ) {
    }

    public function getKeyPath(): string
    {
        return $this->keyPath;
    }

    public function getCertPath(): string
    {
        return $this->certPath;
    }
}
