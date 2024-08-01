<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

interface LocalAdapterInterface extends ExtendedAdapterInterface
{
    public function getLocalPath(string $path): string;
}
