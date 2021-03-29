<?php

namespace App\Flysystem;

use App\Http\Response;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\PathPrefixer;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\ResponseInterface;
use Spatie\FlysystemDropbox\DropboxAdapter;

class RemoteFilesystem extends AbstractFilesystem
{
    protected FilesystemAdapter $remoteAdapter;

    protected PathPrefixer $localPath;

    public function __construct(
        FilesystemAdapter $remoteAdapter,
        string $localPath = null,
        array $config = [],
        PathNormalizer $pathNormalizer = null
    ) {
        if ($remoteAdapter instanceof DropboxAdapter) {
            $config['case_sensitive'] = false;
        }

        $this->localPath = new PathPrefixer($localPath ?? sys_get_temp_dir());
        parent::__construct($remoteAdapter, $config, $pathNormalizer);
    }

    public function getAdapter(): FilesystemAdapter
    {
        return $this->remoteAdapter;
    }

    public function isLocal(): bool
    {
        return false;
    }

    public function getLocalPath(string $path): string
    {
        $tempLocalPath = $this->localPath->prefixPath(
            substr(md5($path), 0, 10) . '_' . basename($path),
        );

        $this->download($path, $tempLocalPath);
        return $tempLocalPath;
    }

    public function withLocalFile(string $path, callable $function)
    {
        $localPath = $this->getLocalPath($path);

        try {
            $returnVal = $function($localPath);
        } finally {
            unlink($localPath);
        }

        return $returnVal;
    }

    public function upload(string $localPath, string $to): void
    {
        if (!file_exists($localPath)) {
            throw new \RuntimeException(sprintf('Source upload file not found at path: %s', $localPath));
        }

        $stream = fopen($localPath, 'rb');

        try {
            $this->writeStream($to, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function download(string $from, string $localPath): void
    {
        if (file_exists($localPath)) {
            if (filemtime($localPath) >= $this->lastModified($from)) {
                touch($localPath);
            }
            unlink($localPath);
        }

        $stream = $this->readStream($from);

        file_put_contents($localPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    public function streamToResponse(
        Response $response,
        string $path,
        string $fileName = null,
        string $disposition = 'attachment'
    ): ResponseInterface {
        $localPath = $this->getLocalPath($path);
        $mime = new FinfoMimeTypeDetector();

        return $this->doStreamToResponse(
            $response,
            $localPath,
            filesize($localPath),
            $mime->detectMimeTypeFromFile($localPath),
            $fileName,
            $disposition
        );
    }
}
