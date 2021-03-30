<?php

namespace App\Flysystem;

use App\Flysystem\Adapter\AdapterInterface;
use League\Flysystem\PathNormalizer;
use League\Flysystem\PathPrefixer;
use Spatie\FlysystemDropbox\DropboxAdapter;

class RemoteFilesystem extends AbstractFilesystem
{
    protected AdapterInterface $remoteAdapter;

    protected PathPrefixer $localPath;

    public function __construct(
        AdapterInterface $remoteAdapter,
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

    /** @inheritDoc */
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
}
