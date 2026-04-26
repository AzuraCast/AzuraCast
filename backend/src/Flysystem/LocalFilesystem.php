<?php

declare(strict_types=1);

namespace App\Flysystem;

use App\Flysystem\Adapter\LocalAdapterInterface;
use League\Flysystem\PathNormalizer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;

final class LocalFilesystem extends AbstractFilesystem
{
    private readonly LocalAdapterInterface $localAdapter;

    public function __construct(
        LocalAdapterInterface $adapter,
        array $config = [],
        ?PathNormalizer $pathNormalizer = null
    ) {
        $this->localAdapter = $adapter;

        parent::__construct($adapter, $config, $pathNormalizer);
    }

    /** @inheritDoc */
    public function isLocal(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function getLocalPath(string $path): string
    {
        return $this->localAdapter->getLocalPath(
            $this->pathNormalizer->normalizePath($path)
        );
    }

    /** @inheritDoc */
    public function upload(string $localPath, string $to): void
    {
        $this->localAdapter->upload(
            $localPath,
            $this->pathNormalizer->normalizePath($to)
        );
    }

    /** @inheritDoc */
    public function download(string $from, string $localPath): void
    {
        $this->localAdapter->download(
            $this->pathNormalizer->normalizePath($from),
            $localPath
        );
    }

    /** @inheritDoc */
    public function withLocalFile(string $path, callable $function): mixed
    {
        $localPath = $this->getLocalPath($path);
        return $function($localPath);
    }
}
