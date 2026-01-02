<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

use App\Flysystem\Attributes\DirectoryAttributes;
use App\Flysystem\Attributes\FileAttributes;
use DirectoryIterator;
use FilesystemIterator;
use Generator;
use League\Flysystem\Local\LocalFilesystemAdapter as LeagueLocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\SymbolicLinkEncountered;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

final class LocalFilesystemAdapter extends LeagueLocalFilesystemAdapter implements LocalAdapterInterface
{
    private readonly PathPrefixer $pathPrefixer;

    private readonly VisibilityConverter $visibility;

    public function __construct(
        string $location,
        ?VisibilityConverter $visibility = null,
        int $writeFlags = LOCK_EX,
        private readonly int $linkHandling = self::DISALLOW_LINKS,
        ?MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->pathPrefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);

        $this->visibility = $visibility ?: new PortableVisibilityConverter();

        parent::__construct($location, $visibility, $writeFlags, $linkHandling, $mimeTypeDetector);
    }

    public function getLocalPath(string $path): string
    {
        return $this->pathPrefixer->prefixPath($path);
    }

    /**
     * This is only overwritten specifically so we can exclude non-readable files.
     *
     * @inheritDoc
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $location = $this->pathPrefixer->prefixPath($path);

        if (! is_dir($location)) {
            return;
        }

        /** @var iterable<SplFileInfo> $iterator */
        $iterator = $deep
            ? $this->listDirectoryRecursively($location)
            : $this->listDirectory($location);

        foreach ($iterator as $fileInfo) {
            try {
                $item = $this->mapFileInfo($fileInfo);

                if ($item !== false) {
                    yield $item;
                }
            } catch (Throwable $exception) {
                if ($exception instanceof SymbolicLinkEncountered || file_exists($fileInfo->getFilename())) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Same as parent, but skips non-readable files.
     *
     * @param string $location
     * @return Generator<SplFileInfo>
     */
    private function listDirectory(string $location): Generator
    {
        $iterator = new DirectoryIterator($location);

        foreach ($iterator as $item) {
            if ($item->isDot() || !$item->isReadable()) {
                continue;
            }

            yield $item;
        }
    }

    /**
     * Same as parent, but will not throw on unreadable files.
     *
     * @return Generator<SplFileInfo>
     */
    private function listDirectoryRecursively(
        string $path,
        int $mode = RecursiveIteratorIterator::SELF_FIRST
    ): Generator {
        if (! is_dir($path)) {
            return;
        }

        if (
            !in_array(
                $mode,
                [
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CHILD_FIRST,
                ],
                true
            )
        ) {
            $mode = RecursiveIteratorIterator::SELF_FIRST;
        }

        yield from new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                function (SplFileInfo $file) {
                    // Skip non-readable files.
                    if (!$file->isReadable()) {
                        return false; // Skip this item if not readable
                    }

                    return true;
                }
            ),
            $mode
        );
    }

    public function getMetadata(string $path): StorageAttributes
    {
        $location = $this->pathPrefixer->prefixPath($path);

        if (!file_exists($location)) {
            throw UnableToRetrieveMetadata::create($location, 'metadata', 'File not found');
        }

        $return = $this->mapFileInfo(new SplFileInfo($location));
        if ($return === false) {
            throw UnableToRetrieveMetadata::create($location, 'metadata', 'Invalid file');
        }

        return $return;
    }

    private function mapFileInfo(SplFileInfo $fileInfo): StorageAttributes|false
    {
        $pathName = $fileInfo->getPathname();

        if ($fileInfo->isLink()) {
            if ($this->linkHandling & self::SKIP_LINKS) {
                return false;
            }

            throw SymbolicLinkEncountered::atLocation($pathName);
        }

        $path = $this->pathPrefixer->stripPrefix($pathName);
        $lastModified = $fileInfo->getMTime();
        $isDirectory = $fileInfo->isDir();
        $permissions = (int)octdec(substr(sprintf('%o', $fileInfo->getPerms()), -4));
        $visibility = $isDirectory
            ? $this->visibility->inverseForDirectory($permissions)
            : $this->visibility->inverseForFile($permissions);

        return $isDirectory
            ? new DirectoryAttributes(
                str_replace('\\', '/', $path),
                $visibility,
                $lastModified
            ) : new FileAttributes(
                str_replace('\\', '/', $path),
                $fileInfo->getSize(),
                $visibility,
                $lastModified
            );
    }
}
