<?php

namespace App\Flysystem\Adapter;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;

class LocalAdapter extends LocalFilesystemAdapter implements AdapterInterface
{
    protected PathPrefixer $pathPrefixer;

    protected VisibilityConverter $visibility;

    public function __construct(
        string $location,
        VisibilityConverter $visibility = null,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->pathPrefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);

        $this->visibility = $visibility ?: new PortableVisibilityConverter();

        parent::__construct($location, $visibility, $writeFlags, $linkHandling, $mimeTypeDetector);
    }

    public function getFullPath(string $path): string
    {
        return $this->pathPrefixer->prefixPath($path);
    }

    /** @inheritDoc */
    public function getMetadata(string $path): StorageAttributes
    {
        $location = $this->pathPrefixer->prefixPath($path);

        if (!file_exists($location)) {
            throw UnableToRetrieveMetadata::create($location, 'metadata', 'File not found');
        }

        $fileInfo = new \SplFileInfo($location);

        $lastModified = $fileInfo->getMTime();
        $isDirectory = $fileInfo->isDir();

        $permissions = $fileInfo->getPerms();
        $visibility = $isDirectory
            ? $this->visibility->inverseForDirectory($permissions)
            : $this->visibility->inverseForFile($permissions);

        return $isDirectory
            ? new DirectoryAttributes($path, $visibility, $lastModified)
            : new FileAttributes($path, $fileInfo->getSize(), $visibility, $lastModified);
    }
}
