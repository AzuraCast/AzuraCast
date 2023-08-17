<?php

declare(strict_types=1);

namespace App\Flysystem\Adapter;

use App\Flysystem\Attributes\DirectoryAttributes;
use App\Flysystem\Attributes\FileAttributes;
use Aws\Api\DateTimeResult;
use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\VisibilityConverter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;

final class AwsS3Adapter extends AwsS3V3Adapter implements ExtendedAdapterInterface
{
    private readonly PathPrefixer $prefixer;

    public function __construct(
        private readonly S3ClientInterface $client,
        private readonly string $bucket,
        string $prefix = '',
        VisibilityConverter $visibility = null,
        MimeTypeDetector $mimeTypeDetector = null,
        array $options = [],
        bool $streamReads = true
    ) {
        $this->prefixer = new PathPrefixer($prefix);

        parent::__construct($client, $bucket, $prefix, $visibility, $mimeTypeDetector, $options, $streamReads);
    }

    /** @inheritDoc */
    public function getMetadata(string $path): StorageAttributes
    {
        $arguments = ['Bucket' => $this->bucket, 'Key' => $this->prefixer->prefixPath($path)];
        $command = $this->client->getCommand('HeadObject', $arguments);

        try {
            $metadata = $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', '', $exception);
        }

        if (str_ends_with($path, '/')) {
            return new DirectoryAttributes(rtrim($path, '/'));
        }

        $mimetype = $metadata['ContentType'] ?? null;
        $fileSize = $metadata['ContentLength'] ?? $metadata['Size'] ?? null;
        $fileSize = $fileSize === null ? null : (int)$fileSize;
        $dateTime = $metadata['LastModified'] ?? null;
        $lastModified = $dateTime instanceof DateTimeResult ? $dateTime->getTimeStamp() : null;
        $visibility = function ($path) {
            return $this->visibility($path)->visibility();
        };

        return new FileAttributes(
            $path,
            $fileSize,
            $visibility,
            $lastModified,
            $mimetype
        );
    }
}
