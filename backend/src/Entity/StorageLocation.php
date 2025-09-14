<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Exception\StorageLocationFullException;
use App\Radio\Quota;
use App\Validator\Constraints as AppAssert;
use Brick\Math\BigInteger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Filesystem\Path;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'storage_location'),
    Attributes\Auditable,
    AppAssert\StorageLocation
]
class StorageLocation implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const string DEFAULT_BACKUPS_PATH = '/var/azuracast/backups';

    #[
        ORM\Column(type: 'string', length: 50, enumType: StorageLocationTypes::class),
        OA\Property(
            description: 'The type of storage location.',
            example: 'station_media'
        )
    ]
    public readonly StorageLocationTypes $type;

    #[
        ORM\Column(type: 'string', length: 50, enumType: StorageLocationAdapters::class),
        OA\Property(
            description: 'The storage adapter to use for this location.',
            example: 'local'
        )
    ]
    public readonly StorageLocationAdapters $adapter;

    #[
        ORM\Column(length: 255, nullable: false),
        OA\Property(
            description: 'The local path, if the local adapter is used, or path prefix for S3/remote adapters.',
            example: '/var/azuracast/stations/azuratest_radio/media'
        )
    ]
    public string $path = '' {
        set => $this->truncateString(Path::canonicalize($value));
    }

    #[
        ORM\Column(name: 's3_credential_key', length: 255, nullable: true),
        OA\Property(
            description: 'The credential key for S3 adapters.',
            example: 'your-key-here'
        )
    ]
    public ?string $s3CredentialKey = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 's3_credential_secret', length: 255, nullable: true),
        OA\Property(
            description: 'The credential secret for S3 adapters.',
            example: 'your-secret-here'
        )
    ]
    public ?string $s3CredentialSecret = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 's3_region', length: 150, nullable: true),
        OA\Property(
            description: 'The region for S3 adapters.',
            example: 'your-region'
        )
    ]
    public ?string $s3Region = null {
        set => $this->truncateNullableString($value, 150);
    }

    #[
        ORM\Column(name: 's3_version', length: 150, nullable: true),
        OA\Property(
            description: 'The API version for S3 adapters.',
            example: 'latest'
        )
    ]
    public ?string $s3Version = 'latest' {
        set => $this->truncateNullableString($value, 150);
    }

    #[
        ORM\Column(name: 's3_bucket', length: 255, nullable: true),
        OA\Property(
            description: 'The S3 bucket name for S3 adapters.',
            example: 'your-bucket-name'
        )
    ]
    public ?string $s3Bucket = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 's3_endpoint', length: 255, nullable: true),
        OA\Property(
            description: 'The optional custom S3 endpoint S3 adapters.',
            example: 'https://your-region.digitaloceanspaces.com'
        )
    ]
    public ?string $s3Endpoint = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 's3_use_path_style', nullable: true),
        OA\Property(example: false)
    ]
    public ?bool $s3UsePathStyle = false;

    #[
        ORM\Column(name: 'dropbox_app_key', length: 50, nullable: true),
        OA\Property(
            description: 'The optional Dropbox App Key.',
            example: ''
        )
    ]
    public ?string $dropboxAppKey = null {
        set => $this->truncateNullableString($value, 50);
    }

    #[
        ORM\Column(name: 'dropbox_app_secret', length: 150, nullable: true),
        OA\Property(
            description: 'The optional Dropbox App Secret.',
            example: ''
        )
    ]
    public ?string $dropboxAppSecret = null {
        set => $this->truncateNullableString($value, 150);
    }

    #[
        ORM\Column(name: 'dropbox_auth_token', length: 255, nullable: true),
        OA\Property(
            description: 'The optional Dropbox Auth Token.',
            example: ''
        )
    ]
    public ?string $dropboxAuthToken = null {
        set => $this->truncateNullableString($value);
    }

    #[ORM\Column(name: 'dropbox_refresh_token', length: 255, nullable: true)]
    public ?string $dropboxRefreshToken = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 'sftp_host', length: 255, nullable: true),
        OA\Property(
            description: 'The host for SFTP adapters',
            example: '127.0.0.1'
        )
    ]
    public ?string $sftpHost = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 'sftp_username', length: 255, nullable: true),
        OA\Property(
            description: 'The username for SFTP adapters',
            example: 'root'
        )
    ]
    public ?string $sftpUsername = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 'sftp_password', length: 255, nullable: true),
        OA\Property(
            description: 'The password for SFTP adapters',
            example: 'abc123'
        )
    ]
    public ?string $sftpPassword = null {
        set => $this->truncateNullableString($value);
    }

    #[
        ORM\Column(name: 'sftp_port', nullable: true),
        OA\Property(
            description: 'The port for SFTP adapters',
            example: 20
        )
    ]
    public ?int $sftpPort = null;

    #[
        ORM\Column(name: 'sftp_private_key', type: 'text', nullable: true),
        OA\Property(
            description: 'The private key for SFTP adapters'
        )
    ]
    public ?string $sftpPrivateKey = null;

    #[
        ORM\Column(name: 'sftp_private_key_pass_phrase', length: 255, nullable: true),
        OA\Property(
            description: 'The private key pass phrase for SFTP adapters'
        )
    ]
    public ?string $sftpPrivateKeyPassPhrase = null {
        set => $this->truncateNullableString($value);
    }

    #[ORM\Column(name: 'storage_quota', type: 'bigint', nullable: true)]
    private string|int|null $storageQuotaRaw = null;

    #[OA\Property(example: '120000')]
    public ?BigInteger $storageQuotaBytes {
        get {
            $size = $this->storageQuotaRaw;
            return (null !== $size && '' !== $size)
                ? BigInteger::of($size)
                : null;
        }
        set {
            $this->storageQuotaRaw = ($value !== null) ? (string)$value : null;
        }
    }

    #[OA\Property(example: '50 GB')]
    public ?string $storageQuota {
        get {
            $rawQuota = $this->storageQuotaBytes;
            return ($rawQuota instanceof BigInteger)
                ? Quota::getReadableSize($rawQuota)
                : null;
        }
        set (BigInteger|string|null $value) {
            $this->storageQuotaBytes = Quota::convertFromReadableSize($value);
        }
    }

    #[ORM\Column(name: 'storage_used', type: 'bigint', nullable: true)]
    #[Attributes\AuditIgnore]
    private string|int|null $storageUsedRaw = null;

    #[OA\Property(example: '60000')]
    public BigInteger $storageUsedBytes {
        get {
            $size = $this->storageUsedRaw;
            return (null !== $size && '' !== $size)
                ? BigInteger::of($size)
                : BigInteger::zero();
        }
        set (BigInteger|null $value) {
            $this->storageUsedRaw = ($value !== null) ? (string)$value : null;
        }
    }

    #[OA\Property(example: '1 GB')]
    public string $storageUsed {
        get => Quota::getReadableSize($this->storageUsedBytes);
        set (BigInteger|string|null $value) {
            $this->storageUsedBytes = Quota::convertFromReadableSize($value);
        }
    }

    /**
     * Increment the current used storage total.
     */
    public function addStorageUsed(BigInteger|int|string $newStorageAmount): void
    {
        if (empty($newStorageAmount)) {
            return;
        }

        $currentStorageUsed = $this->storageUsedBytes;
        $this->storageUsed = (string)$currentStorageUsed->plus($newStorageAmount);
    }

    /**
     * Decrement the current used storage total.
     */
    public function removeStorageUsed(BigInteger|int|string $amountToRemove): void
    {
        if (empty($amountToRemove)) {
            return;
        }

        $storageUsed = $this->storageUsedBytes->minus($amountToRemove);
        if ($storageUsed->isLessThan(0)) {
            $storageUsed = BigInteger::zero();
        }

        $this->storageUsed = (string)$storageUsed;
    }

    #[OA\Property(example: '120000')]
    public ?BigInteger $storageAvailableBytes {
        get {
            $quota = $this->storageQuotaBytes;

            if ($this->adapter->isLocal()) {
                $localPath = $this->path;

                $totalSpaceFloat = disk_total_space($localPath);
                if (is_float($totalSpaceFloat)) {
                    $totalSpace = BigInteger::of($totalSpaceFloat);
                    if (null === $quota || $quota->isGreaterThan($totalSpace)) {
                        return $totalSpace;
                    }
                }
            }

            return $quota ?? null;
        }
    }

    #[OA\Property(example: '1 GB')]
    public string $storageAvailable {
        get {
            $rawSize = $this->storageAvailableBytes;

            return ($rawSize instanceof BigInteger)
                ? Quota::getReadableSize($rawSize)
                : '';
        }
    }

    public function getStorageUsePercentage(): int
    {
        $storageUsed = $this->storageUsedBytes;
        $storageAvailable = $this->storageAvailableBytes;

        if (null === $storageAvailable) {
            return 0;
        }

        return Quota::getPercentage($storageUsed, $storageAvailable);
    }

    /** @var Collection<int, StationMedia> */
    #[ORM\OneToMany(targetEntity: StationMedia::class, mappedBy: 'storage_location')]
    public private(set) Collection $media;

    /** @var Collection<int, UnprocessableMedia> */
    #[ORM\OneToMany(targetEntity: UnprocessableMedia::class, mappedBy: 'storage_location')]
    public private(set) Collection $unprocessable_media;

    public function __construct(
        StorageLocationTypes $type,
        StorageLocationAdapters $adapter
    ) {
        $this->type = $type;
        $this->adapter = $adapter;

        $this->media = new ArrayCollection();
        $this->unprocessable_media = new ArrayCollection();
    }

    public function isStorageFull(): bool
    {
        $quota = $this->storageQuotaBytes;
        if ($quota === null) {
            return false;
        }

        $used = $this->storageUsedBytes;

        return ($used->compareTo($quota) !== -1);
    }

    public function canHoldFile(BigInteger|int|string $size): bool
    {
        if (empty($size)) {
            return true;
        }

        $quota = $this->storageQuotaBytes;
        if ($quota === null) {
            return true;
        }

        $newStorageUsed = $this->storageUsedBytes->plus($size);
        return ($newStorageUsed->compareTo($quota) === -1);
    }

    public function errorIfFull(): void
    {
        if ($this->isStorageFull()) {
            throw new StorageLocationFullException();
        }
    }

    public function getUri(?string $suffix = null): string
    {
        $adapterClass = $this->adapter->getAdapterClass();
        return $adapterClass::getUri($this, $suffix);
    }

    public function getFilteredPath(): string
    {
        $adapterClass = $this->adapter->getAdapterClass();
        return $adapterClass::filterPath($this->path);
    }

    public function __clone(): void
    {
        $this->media = new ArrayCollection();
        $this->unprocessable_media = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->adapter->getName() . ': ' . $this->getUri();
    }
}
