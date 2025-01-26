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

    private readonly VisibilityConverter $visibilityConverter;

    public function __construct(
        LocalAdapterInterface $adapter,
        array $config = [],
        PathNormalizer $pathNormalizer = null,
        VisibilityConverter $visibilityConverter = null
    ) {
        $this->localAdapter = $adapter;
        $this->visibilityConverter = $visibilityConverter ?? new PortableVisibilityConverter();

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
        return $this->localAdapter->getLocalPath($path);
    }

    /** @inheritDoc */
    public function upload(string $localPath, string $to): void
    {
        $destPath = $this->getLocalPath($to);

        $this->ensureDirectoryExists(
            dirname($destPath),
            $this->visibilityConverter->defaultForDirectories()
        );

        if (!@copy($localPath, $destPath)) {
            throw UnableToCopyFile::fromLocationTo($localPath, $destPath);
        }
    }

    /** @inheritDoc */
    public function download(string $from, string $localPath): void
    {
        $sourcePath = $this->getLocalPath($from);

        $this->ensureDirectoryExists(
            dirname($localPath),
            $this->visibilityConverter->defaultForDirectories()
        );

        if (!@copy($sourcePath, $localPath)) {
            throw UnableToCopyFile::fromLocationTo($sourcePath, $localPath);
        }
    }

    /** @inheritDoc */
    public function withLocalFile(string $path, callable $function)
    {
        $localPath = $this->getLocalPath($path);
        return $function($localPath);
    }

    private function ensureDirectoryExists(string $dirname, int $visibility): void
    {
        if (is_dir($dirname)) {
            return;
        }

        error_clear_last();

        if (!@mkdir($dirname, $visibility, true)) {
            $mkdirError = error_get_last();
        }

        clearstatcache(false, $dirname);

        if (!is_dir($dirname)) {
            $errorMessage = $mkdirError['message'] ?? '';

            throw UnableToCreateDirectory::atLocation($dirname, $errorMessage);
        }
    }
}
