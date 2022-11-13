<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use App\Flysystem\Adapter\AwsS3Adapter;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use Aws\S3\S3Client;
use InvalidArgumentException;

final class S3StorageLocationAdapter extends AbstractStorageLocationLocationAdapter
{
    public function getType(): StorageLocationAdapters
    {
        return StorageLocationAdapters::S3;
    }

    public static function filterPath(string $path): string
    {
        return trim($path, '/');
    }

    public function getUri(?string $suffix = null): string
    {
        $path = $this->applyPath($suffix);

        $bucket = $this->storageLocation->getS3Bucket();
        if (null === $bucket) {
            return 'No S3 Bucket Specified';
        }

        try {
            $client = $this->getClient();
            if (empty($path)) {
                $objectUrl = $client->getObjectUrl($bucket, '/');
                return rtrim($objectUrl, '/');
            }

            return $client->getObjectUrl($bucket, ltrim($path, '/'));
        } catch (InvalidArgumentException $e) {
            return 'Invalid URI (' . $e->getMessage() . ')';
        }
    }

    public function getStorageAdapter(): ExtendedAdapterInterface
    {
        $filteredPath = self::filterPath($this->storageLocation->getPath());

        $bucket = $this->storageLocation->getS3Bucket();
        if (null === $bucket) {
            throw new \RuntimeException('Amazon S3 bucket is empty.');
        }

        return new AwsS3Adapter($this->getClient(), $bucket, $filteredPath);
    }

    public function validate(): void
    {
        $client = $this->getClient();
        $client->listObjectsV2(
            [
                'Bucket' => $this->storageLocation->getS3Bucket(),
                'max-keys' => 1,
            ]
        );

        parent::validate();
    }

    private function getClient(): S3Client
    {
        $s3Options = array_filter(
            [
                'credentials' => [
                    'key' => $this->storageLocation->getS3CredentialKey(),
                    'secret' => $this->storageLocation->getS3CredentialSecret(),
                ],
                'region' => $this->storageLocation->getS3Region(),
                'version' => $this->storageLocation->getS3Version(),
                'endpoint' => $this->storageLocation->getS3Endpoint(),
            ]
        );
        return new S3Client($s3Options);
    }
}
