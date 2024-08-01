<?php

declare(strict_types=1);

namespace App\Flysystem;

use App\Flysystem\Adapter\ExtendedAdapterInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

interface ExtendedFilesystemInterface extends FilesystemOperator
{
    /**
     * @return ExtendedAdapterInterface The underlying filesystem adapter.
     */
    public function getAdapter(): ExtendedAdapterInterface;

    /**
     * @return bool Whether this filesystem is directly located on disk.
     */
    public function isLocal(): bool;

    /**
     * @param string $path The original path of the file on the filesystem.
     *
     * @return string A path that will be guaranteed to be local to the filesystem.
     */
    public function getLocalPath(string $path): string;

    /**
     * @param string $path
     *
     * @return StorageAttributes Metadata for the specified path.
     */
    public function getMetadata(string $path): StorageAttributes;

    public function isDir(string $path): bool;

    public function isFile(string $path): bool;

    /**
     * Call a callable function with a path that is guaranteed to be a local path, even if
     * this filesystem is a remote one, by copying to a temporary directory first in the
     * case of remote filesystems.
     *
     * @param string $path
     * @param callable $function
     *
     * @return mixed
     */
    public function withLocalFile(string $path, callable $function);

    /**
     * @param string $localPath
     * @param string $to
     */
    public function uploadAndDeleteOriginal(string $localPath, string $to): void;

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
