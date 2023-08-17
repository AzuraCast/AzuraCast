<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Traits\HasLinks;
use App\Traits\LoadFromParentObject;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_StorageLocation',
    type: 'object'
)]
final class StorageLocation
{
    use HasLinks;
    use LoadFromParentObject;

    #[OA\Property(example: 1)]
    public int $id;

    #[OA\Property(
        description: 'The type of storage location.',
        example: 'station_media'
    )]
    public string $type;

    #[OA\Property(
        description: 'The storage adapter to use for this location.',
        example: 'local'
    )]
    public string $adapter;

    #[OA\Property(
        description: 'The local path, if the local adapter is used, or path prefix for S3/remote adapters.',
        example: '/var/azuracast/stations/azuratest_radio/media'
    )]
    public ?string $path = null;

    #[OA\Property(
        description: 'The credential key for S3 adapters.',
        example: 'your-key-here'
    )]
    public ?string $s3CredentialKey = null;

    #[OA\Property(
        description: 'The credential secret for S3 adapters.',
        example: 'your-secret-here'
    )]
    public ?string $s3CredentialSecret = null;

    #[OA\Property(
        description: 'The region for S3 adapters.',
        example: 'your-region'
    )]
    public ?string $s3Region = null;

    #[OA\Property(
        description: 'The API version for S3 adapters.',
        example: 'latest'
    )]
    public ?string $s3Version = null;

    #[OA\Property(
        description: 'The S3 bucket name for S3 adapters.',
        example: 'your-bucket-name'
    )]
    public ?string $s3Bucket = null;

    #[OA\Property(
        description: 'The optional custom S3 endpoint S3 adapters.',
        example: 'https://your-region.digitaloceanspaces.com'
    )]
    public ?string $s3Endpoint = null;

    #[OA\Property(
        description: 'The optional Dropbox App Key.',
        example: ''
    )]
    public ?string $dropboxAppKey = null;

    #[OA\Property(
        description: 'The optional Dropbox App Secret.',
        example: ''
    )]
    public ?string $dropboxAppSecret = null;

    #[OA\Property(
        description: 'The optional Dropbox Auth Token.',
        example: ''
    )]
    public ?string $dropboxAuthToken = null;

    #[OA\Property(
        description: 'The host for SFTP adapters',
        example: '127.0.0.1'
    )]
    public ?string $sftpHost = null;

    #[OA\Property(
        description: 'The username for SFTP adapters',
        example: 'root'
    )]
    public ?string $sftpUsername = null;

    #[OA\Property(
        description: 'The password for SFTP adapters',
        example: 'abc123'
    )]
    public ?string $sftpPassword = null;

    #[OA\Property(
        description: 'The port for SFTP adapters',
        example: 20
    )]
    public ?int $sftpPort = null;

    #[OA\Property(
        description: 'The private key for SFTP adapters'
    )]
    public ?string $sftpPrivateKey = null;

    #[OA\Property(
        description: 'The private key pass phrase for SFTP adapters'
    )]
    public ?string $sftpPrivateKeyPassPhrase = null;

    #[OA\Property(example: '50 GB')]
    public ?string $storageQuota = null;

    #[OA\Property(example: '120000')]
    public ?string $storageQuotaBytes = null;

    #[OA\Property(example: '1 GB')]
    public ?string $storageUsed = null;

    #[OA\Property(example: '60000')]
    public ?string $storageUsedBytes = null;

    #[OA\Property(example: '1 GB')]
    public ?string $storageAvailable = null;

    #[OA\Property(example: '120000')]
    public ?string $storageAvailableBytes = null;

    #[OA\Property(example: '75')]
    public ?int $storageUsedPercent = null;

    #[OA\Property(example: 'true')]
    public bool $isFull = true;

    #[OA\Property(
        description: 'The URI associated with the storage location.',
        example: '/var/azuracast/www'
    )]
    public string $uri;

    #[OA\Property(
        description: 'The stations using this storage location, if any.',
        items: new OA\Items(type: 'string', example: 'AzuraTest Radio')
    )
    ]
    public ?array $stations = [];
}
