<?php

namespace App\Radio;

class Certificate
{
    protected string $keyPath;

    protected string $certPath;

    public function __construct(string $keyPath, string $certPath)
    {
        $this->keyPath = $keyPath;
        $this->certPath = $certPath;
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
