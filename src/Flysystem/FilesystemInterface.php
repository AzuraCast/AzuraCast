<?php

namespace App\Flysystem;

use App\Http\Response;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use Psr\Http\Message\ResponseInterface;

interface FilesystemInterface extends FilesystemOperator
{
    /**
     * @return FilesystemAdapter The underlying filesystem adapter.
     */
    public function getAdapter(): FilesystemAdapter;

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


    /**
     * Read a stream from the filesystem and directly write it to a PSR-7-compatible response object.
     *
     * @param Response $response The original PSR-7 response.
     * @param string $path The path on the filesystem to stream.
     * @param string|null $fileName
     * @param string $disposition
     *
     * @return ResponseInterface The modified PSR-7 response.
     */
    public function streamToResponse(
        Response $response,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface;
}
