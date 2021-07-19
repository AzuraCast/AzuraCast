<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity;
use App\Traits\LoadFromParentObject;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Admin_StorageLocation")
 */
class StorageLocation
{
    use Entity\Api\Traits\HasLinks;
    use LoadFromParentObject;

    /**
     * @OA\Property(example=1)
     * @var int
     */
    public int $id;

    /**
     * @OA\Property(example="station_media")
     * @var string The type of storage location.
     */
    public string $type;

    /**
     * @OA\Property(example="local")
     * @var string The storage adapter to use for this location.
     */
    public string $adapter;

    /**
     * @OA\Property(example="/var/azuracast/stations/azuratest_radio/media")
     * @var string|null The local path, if the local adapter is used, or path prefix for S3/remote adapters.
     */
    public ?string $path = null;

    /**
     * @OA\Property(example="your-key-here")
     * @var string|null The credential key for S3 adapters.
     */
    public ?string $s3CredentialKey = null;

    /**
     * @OA\Property(example="your-secret-here")
     * @var string|null The credential secret for S3 adapters.
     */
    public ?string $s3CredentialSecret = null;

    /**
     * @OA\Property(example="your-region")
     * @var string|null The region for S3 adapters.
     */
    public ?string $s3Region = null;

    /**
     * @OA\Property(example="latest")
     * @var string|null The API version for S3 adapters.
     */
    public ?string $s3Version = null;

    /**
     * @OA\Property(example="your-bucket-name")
     * @var string|null The S3 bucket name for S3 adapters.
     */
    public ?string $s3Bucket = null;

    /**
     * @OA\Property(example="https://your-region.digitaloceanspaces.com")
     * @var string|null The optional custom S3 endpoint S3 adapters.
     */
    public ?string $s3Endpoint = null;

    /**
     * @OA\Property(example="50 GB")
     * @var string|null
     */
    public ?string $storageQuota = null;

    /**
     * @OA\Property(example="1 GB")
     * @var string|null
     */
    public ?string $storageUsed = null;

    /**
     * The URI associated with the storage location.
     *
     * @OA\Property(example="/var/azuracast/www")
     * @var string
     */
    public string $uri;

    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="AzuraTest Radio"
     *     )
     * )
     * @var array|null The stations using this storage location, if any.
     */
    public ?array $stations = [];
}
