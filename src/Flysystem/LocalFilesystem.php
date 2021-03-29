<?php

namespace App\Flysystem;

use App\Http\Response;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use Psr\Http\Message\ResponseInterface;

class LocalFilesystem extends AbstractFilesystem
{
    protected LocalAdapter $localAdapter;

    public function __construct(LocalAdapter $adapter, array $config = [], PathNormalizer $pathNormalizer = null)
    {
        $this->localAdapter = $adapter;

        parent::__construct($adapter, $config, $pathNormalizer);
    }

    public function getAdapter(): FilesystemAdapter
    {
        return $this->localAdapter;
    }

    public function isLocal(): bool
    {
        return true;
    }

    public function getLocalPath(string $path): string
    {
        return $this->localAdapter->getFullPath($path);
    }

    public function upload(string $localPath, string $to): void
    {
        $destPath = $this->localAdapter->getFullPath($to);
        copy($localPath, $destPath);
    }

    public function download(string $from, string $localPath): void
    {
        $sourcePath = $this->localAdapter->getFullPath($from);
        copy($sourcePath, $localPath);
    }

    public function withLocalFile(string $path, callable $function)
    {
        $localPath = $this->getLocalPath($path);
        return $function($localPath);
    }

    public function streamToResponse(
        Response $response,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface {
        return $this->doStreamToResponse(
            $response,
            $this->getLocalPath($path),
            $this->fileSize($path),
            $this->mimeType($path),
            $fileName,
            $disposition
        );
    }
}
