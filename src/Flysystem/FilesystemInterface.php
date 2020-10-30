<?php

namespace App\Flysystem;

use App\Http\Response;
use Iterator;
use Psr\Http\Message\ResponseInterface;

interface FilesystemInterface extends \League\Flysystem\FilesystemInterface
{
    public function clearCache(bool $inMemoryOnly = false): void;

    public function getFullPath(string $uri): string;

    /**
     * Create an iterator that loops through the entire contents of a given prefix.
     *
     * @param string $path
     * @param array $iteratorOptions
     *
     */
    public function createIterator(string $path, array $iteratorOptions = []): Iterator;

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
