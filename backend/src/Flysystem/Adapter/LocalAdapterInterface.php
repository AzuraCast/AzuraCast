<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

use League\Flysystem\UnableToCopyFile;

interface LocalAdapterInterface extends ExtendedAdapterInterface
{
    public function getLocalPath(string $path): string;

    /**
     * @param string $localPath
     * @param string $to
     */
    public function upload(string $localPath, string $to): void;

    /**
     * @param string $from
     * @param string $localPath
     */
    public function download(string $from, string $localPath): void;
}
