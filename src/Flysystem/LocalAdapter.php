<?php

namespace App\Flysystem;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;

class LocalAdapter extends LocalFilesystemAdapter
{
    protected PathPrefixer $pathPrefixer;

    public function __construct(
        string $location,
        VisibilityConverter $visibility = null,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->pathPrefixer = new PathPrefixer($location, DIRECTORY_SEPARATOR);

        parent::__construct($location, $visibility, $writeFlags, $linkHandling, $mimeTypeDetector);
    }

    public function getFullPath(string $path): string
    {
        return $this->pathPrefixer->prefixPath($path);
    }
}
