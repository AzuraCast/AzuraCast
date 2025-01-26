<?php

declare(strict_types=1);

namespace App\Flysystem;

use App\Flysystem\Adapter\ExtendedAdapterInterface;
use League\Flysystem\PathNormalizer;
use League\Flysystem\PathPrefixer;
use RuntimeException;

final class RemoteFilesystem extends AbstractFilesystem
{
    private readonly PathPrefixer $localPath;

    public function __construct(
        ExtendedAdapterInterface $remoteAdapter,
        string $localPath = null,
        array $config = [],
        PathNormalizer $pathNormalizer = null
    ) {
        $this->localPath = new PathPrefixer($localPath ?? sys_get_temp_dir());
        parent::__construct($remoteAdapter, $config, $pathNormalizer);
    }

    /** @inheritDoc */
    public function isLocal(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function getLocalPath(string $path): string
    {
        $tempLocalPath = $this->localPath->prefixPath(
            substr(md5($path), 0, 10) . '_' . basename($path),
        );

        $this->download($path, $tempLocalPath);
        return $tempLocalPath;
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
    public function upload(string $localPath, string $to): void
    {
        if (!is_file($localPath)) {
            throw new RuntimeException(sprintf('Source upload file not found at path: %s', $localPath));
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

    /** @inheritDoc */
    public function download(string $from, string $localPath): void
    {
        if (is_file($localPath)) {
            if (filemtime($localPath) >= $this->lastModified($from)) {
                touch($localPath);
                return;
            }

            unlink($localPath);
        }

        $stream = $this->readStream($from);

        file_put_contents($localPath, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}
