<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Radio\Quota;
use App\Validator\Constraints as AppAssert;
use Aws\S3\S3Client;
use Azura\Files\Adapter\AwsS3\AwsS3Adapter;
use Azura\Files\Adapter\Dropbox\DropboxAdapter;
use Azura\Files\Adapter\ExtendedAdapterInterface;
use Azura\Files\Adapter\Local\LocalFilesystemAdapter;
use Azura\Files\Adapter\LocalAdapterInterface;
use Azura\Files\ExtendedFilesystemInterface;
use Azura\Files\LocalFilesystem;
use Azura\Files\RemoteFilesystem;
use Brick\Math\BigInteger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Spatie\Dropbox\Client;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity,
    ORM\Table(name: 'storage_location'),
    Attributes\Auditable,
    AppAssert\StorageLocation
]
class StorageLocation implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    public const TYPE_BACKUP = 'backup';
    public const TYPE_STATION_MEDIA = 'station_media';
    public const TYPE_STATION_RECORDINGS = 'station_recordings';
    public const TYPE_STATION_PODCASTS = 'station_podcasts';

    public const ADAPTER_LOCAL = 'local';
    public const ADAPTER_S3 = 's3';
    public const ADAPTER_DROPBOX = 'dropbox';

    public const DEFAULT_BACKUPS_PATH = '/var/azuracast/backups';

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [
        StorageLocation::TYPE_BACKUP,
        StorageLocation::TYPE_STATION_MEDIA,
        StorageLocation::TYPE_STATION_RECORDINGS,
        StorageLocation::TYPE_STATION_PODCASTS,
    ])]
    protected string $type;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [
        StorageLocation::ADAPTER_LOCAL,
        StorageLocation::ADAPTER_S3,
        StorageLocation::ADAPTER_DROPBOX,
    ])]
    protected string $adapter = self::ADAPTER_LOCAL;

    #[ORM\Column(length: 255, nullable: false)]
    protected string $path = '';

    #[ORM\Column(name: 's3_credential_key', length: 255, nullable: true)]
    protected ?string $s3CredentialKey = null;

    #[ORM\Column(name: 's3_credential_secret', length: 255, nullable: true)]
    protected ?string $s3CredentialSecret = null;

    #[ORM\Column(name: 's3_region', length: 150, nullable: true)]
    protected ?string $s3Region = null;

    #[ORM\Column(name: 's3_version', length: 150, nullable: true)]
    protected ?string $s3Version = 'latest';

    #[ORM\Column(name: 's3_bucket', length: 255, nullable: true)]
    protected ?string $s3Bucket = null;

    #[ORM\Column(name: 's3_endpoint', length: 255, nullable: true)]
    protected ?string $s3Endpoint = null;

    #[ORM\Column(name: 'dropbox_auth_token', length: 255, nullable: true)]
    protected ?string $dropboxAuthToken = null;

    #[ORM\Column(name: 'storage_quota', type: 'bigint', nullable: true)]
    protected ?string $storageQuota = null;

    // Used for API generation.
    protected ?string $storageQuotaBytes = null;

    #[ORM\Column(name: 'storage_used', type: 'bigint', nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?string $storageUsed = null;

    // Used for API generation.
    protected ?string $storageUsedBytes = null;

    #[ORM\OneToMany(mappedBy: 'storage_location', targetEntity: StationMedia::class)]
    protected Collection $media;

    public function __construct(string $type, string $adapter)
    {
        $this->type = $type;
        $this->adapter = $adapter;

        $this->media = new ArrayCollection();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFilteredPath(): string
    {
        return match ($this->adapter) {
            self::ADAPTER_S3, self::ADAPTER_DROPBOX => trim($this->path, '/'),
            default => rtrim($this->path, '/')
        };
    }

    public function applyPath(?string $suffix = null): string
    {
        $suffix = (null !== $suffix)
            ? '/' . ltrim($suffix, '/')
            : '';

        return $this->path . $suffix;
    }

    public function setPath(string $path): void
    {
        $this->path = $this->truncateString($path);
    }

    public function getS3CredentialKey(): ?string
    {
        return $this->s3CredentialKey;
    }

    public function setS3CredentialKey(?string $s3CredentialKey): void
    {
        $this->s3CredentialKey = $this->truncateNullableString($s3CredentialKey);
    }

    public function getS3CredentialSecret(): ?string
    {
        return $this->s3CredentialSecret;
    }

    public function setS3CredentialSecret(?string $s3CredentialSecret): void
    {
        $this->s3CredentialSecret = $this->truncateNullableString($s3CredentialSecret);
    }

    public function getS3Region(): ?string
    {
        return $this->s3Region;
    }

    public function setS3Region(?string $s3Region): void
    {
        $this->s3Region = $s3Region;
    }

    public function getS3Version(): ?string
    {
        return $this->s3Version;
    }

    public function setS3Version(?string $s3Version): void
    {
        $this->s3Version = $s3Version;
    }

    public function getS3Bucket(): ?string
    {
        return $this->s3Bucket;
    }

    public function setS3Bucket(?string $s3Bucket): void
    {
        $this->s3Bucket = $s3Bucket;
    }

    public function getS3Endpoint(): ?string
    {
        return $this->s3Endpoint;
    }

    public function setS3Endpoint(?string $s3Endpoint): void
    {
        $this->s3Endpoint = $this->truncateNullableString($s3Endpoint);
    }

    public function getDropboxAuthToken(): ?string
    {
        return $this->dropboxAuthToken;
    }

    public function setDropboxAuthToken(?string $dropboxAuthToken): void
    {
        $this->dropboxAuthToken = $dropboxAuthToken;
    }

    public function isLocal(): bool
    {
        return self::ADAPTER_LOCAL === $this->adapter;
    }

    public function getStorageQuota(): ?string
    {
        $raw_quota = $this->getStorageQuotaBytes();

        return ($raw_quota instanceof BigInteger)
            ? Quota::getReadableSize($raw_quota)
            : '';
    }

    /**
     * @param string|BigInteger|null $storageQuota
     */
    public function setStorageQuota(BigInteger|string|null $storageQuota): void
    {
        $storageQuota = (string)Quota::convertFromReadableSize($storageQuota);
        $this->storageQuota = !empty($storageQuota) ? $storageQuota : null;
    }

    public function getStorageQuotaBytes(): ?BigInteger
    {
        $size = $this->storageQuota;

        return (null !== $size && '' !== $size)
            ? BigInteger::of($size)
            : null;
    }

    public function getStorageUsed(): ?string
    {
        $raw_size = $this->getStorageUsedBytes();
        return Quota::getReadableSize($raw_size);
    }

    /**
     * @param string|BigInteger|null $storageUsed
     */
    public function setStorageUsed(BigInteger|string|null $storageUsed): void
    {
        $storageUsed = (string)Quota::convertFromReadableSize($storageUsed);
        $this->storageUsed = !empty($storageUsed) ? $storageUsed : null;
    }

    public function getStorageUsedBytes(): BigInteger
    {
        $size = $this->storageUsed;

        return (null !== $size && '' !== $size)
            ? BigInteger::of($size)
            : BigInteger::zero();
    }

    /**
     * Increment the current used storage total.
     *
     * @param int|string|BigInteger $newStorageAmount
     */
    public function addStorageUsed(BigInteger|int|string $newStorageAmount): void
    {
        if (empty($newStorageAmount)) {
            return;
        }

        $currentStorageUsed = $this->getStorageUsedBytes();
        $this->storageUsed = (string)$currentStorageUsed->plus($newStorageAmount);
    }

    /**
     * Decrement the current used storage total.
     *
     * @param int|string|BigInteger $amountToRemove
     */
    public function removeStorageUsed(BigInteger|int|string $amountToRemove): void
    {
        if (empty($amountToRemove)) {
            return;
        }

        $storageUsed = $this->getStorageUsedBytes()->minus($amountToRemove);
        if ($storageUsed->isLessThan(0)) {
            $storageUsed = BigInteger::zero();
        }

        $this->storageUsed = (string)$storageUsed;
    }

    public function getStorageAvailable(): string
    {
        $raw_size = $this->getRawStorageAvailable();

        return ($raw_size instanceof BigInteger)
            ? Quota::getReadableSize($raw_size)
            : '';
    }

    public function getRawStorageAvailable(): ?BigInteger
    {
        $quota = $this->getStorageQuotaBytes();

        if ($this->isLocal()) {
            $localPath = $this->getPath();

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

    public function isStorageFull(): bool
    {
        $available = $this->getRawStorageAvailable();
        if ($available === null) {
            return false;
        }

        $used = $this->getStorageUsedBytes();

        return ($used->compareTo($available) !== -1);
    }

    public function getStorageUsePercentage(): int
    {
        $storageUsed = $this->getStorageUsedBytes();
        $storageAvailable = $this->getRawStorageAvailable();

        if (null === $storageAvailable) {
            return 0;
        }

        return Quota::getPercentage($storageUsed, $storageAvailable);
    }

    /**
     * @return Collection|StationMedia[]
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function getUri(?string $suffix = null): string
    {
        $path = $this->applyPath($suffix);

        return match ($this->adapter) {
            self::ADAPTER_S3 => $this->getS3ObjectUri($suffix),
            self::ADAPTER_DROPBOX => 'dropbox://' . $this->dropboxAuthToken . ltrim($path, '/'),
            default => $path,
        };
    }

    protected function getS3ObjectUri(?string $suffix = null): string
    {
        $path = $this->applyPath($suffix);

        $bucket = $this->s3Bucket;
        if (null === $bucket) {
            return 'No S3 Bucket Specified';
        }

        try {
            $client = $this->getS3Client();
            if (empty($path)) {
                $objectUrl = $client->getObjectUrl($bucket, '/');
                return rtrim($objectUrl, '/');
            }

            return $client->getObjectUrl($bucket, ltrim($path, '/'));
        } catch (InvalidArgumentException $e) {
            return 'Invalid URI (' . $e->getMessage() . ')';
        }
    }

    public function validate(): void
    {
        if (self::ADAPTER_S3 === $this->adapter) {
            $client = $this->getS3Client();
            $client->listObjectsV2(
                [
                    'Bucket' => $this->s3Bucket,
                    'max-keys' => 1,
                ]
            );
        }

        $adapter = $this->getStorageAdapter();
        $adapter->fileExists('/test');
    }

    public function getStorageAdapter(): ExtendedAdapterInterface
    {
        $filteredPath = $this->getFilteredPath();

        switch ($this->adapter) {
            case self::ADAPTER_S3:
                $bucket = $this->s3Bucket;
                if (null === $bucket) {
                    throw new \RuntimeException('Amazon S3 bucket is empty.');
                }
                return new AwsS3Adapter($this->getS3Client(), $bucket, $filteredPath);

            case self::ADAPTER_DROPBOX:
                return new DropboxAdapter($this->getDropboxClient(), $filteredPath);

            default:
                return new LocalFilesystemAdapter($filteredPath);
        }
    }

    protected function getS3Client(): S3Client
    {
        if (self::ADAPTER_S3 !== $this->adapter) {
            throw new InvalidArgumentException('This storage location is not using the S3 adapter.');
        }

        $s3Options = array_filter(
            [
                'credentials' => [
                    'key' => $this->s3CredentialKey,
                    'secret' => $this->s3CredentialSecret,
                ],
                'region' => $this->s3Region,
                'version' => $this->s3Version,
                'endpoint' => $this->s3Endpoint,
            ]
        );
        return new S3Client($s3Options);
    }

    protected function getDropboxClient(): Client
    {
        if (self::ADAPTER_DROPBOX !== $this->adapter) {
            throw new InvalidArgumentException('This storage location is not using the Dropbox adapter.');
        }

        return new Client($this->dropboxAuthToken);
    }

    public function getFilesystem(): ExtendedFilesystemInterface
    {
        $adapter = $this->getStorageAdapter();

        return ($adapter instanceof LocalAdapterInterface)
            ? new LocalFilesystem($adapter)
            : new RemoteFilesystem($adapter);
    }

    public function __toString(): string
    {
        $adapterNames = [
            self::ADAPTER_LOCAL => 'Local',
            self::ADAPTER_S3 => 'S3',
            self::ADAPTER_DROPBOX => 'Dropbox',
        ];
        return $adapterNames[$this->adapter] . ': ' . $this->getUri();
    }
}
