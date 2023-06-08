<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

use App\Flysystem\Attributes\DirectoryAttributes;
use App\Flysystem\Attributes\FileAttributes;
use League\Flysystem\PathPrefixer;
use League\Flysystem\PhpseclibV3\ConnectionProvider;
use League\Flysystem\PhpseclibV3\SftpAdapter as LeagueSftpAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

final class SftpAdapter extends LeagueSftpAdapter implements ExtendedAdapterInterface
{
    private const NET_SFTP_TYPE_DIRECTORY = 2;

    private readonly VisibilityConverter $visibilityConverter;

    private readonly PathPrefixer $prefixer;

    public function __construct(
        private readonly ConnectionProvider $connectionProvider,
        string $root,
        VisibilityConverter $visibilityConverter = null,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->visibilityConverter = $visibilityConverter ?: new PortableVisibilityConverter();
        $this->prefixer = new PathPrefixer($root);

        $mimeTypeDetector ??= new FinfoMimeTypeDetector();

        parent::__construct($connectionProvider, $root, $visibilityConverter, $mimeTypeDetector);
    }

    /** @inheritDoc */
    public function getMetadata(string $path): StorageAttributes
    {
        $location = $this->prefixer->prefixPath($path);
        $connection = $this->connectionProvider->provideConnection();
        $stat = $connection->stat($location);

        if (!is_array($stat)) {
            throw UnableToRetrieveMetadata::create($path, 'metadata');
        }

        $attributes = $this->convertListingToAttributes($path, $stat);

        if (!$attributes instanceof FileAttributes) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', 'path is not a file');
        }

        return $attributes;
    }

    private function convertListingToAttributes(string $path, array $attributes): StorageAttributes
    {
        $permissions = $attributes['mode'] & 0777;
        $lastModified = $attributes['mtime'] ?? null;

        if ($attributes['type'] === self::NET_SFTP_TYPE_DIRECTORY) {
            return new DirectoryAttributes(
                ltrim($path, '/'),
                $this->visibilityConverter->inverseForDirectory($permissions),
                $lastModified
            );
        }

        return new FileAttributes(
            $path,
            $attributes['size'],
            $this->visibilityConverter->inverseForFile($permissions),
            $lastModified
        );
    }
}
