<?php

namespace App\Flysystem\Adapter;

interface LocalAdapterInterface extends ExtendedAdapterInterface
{
    public function getLocalPath(string $path): string;
}
