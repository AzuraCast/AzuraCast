<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Flysystem\Filesystem;
use App\Radio\Quota;
use App\Validator\Constraints as AppAssert;
use Aws\S3\S3Client;
use Brick\Math\BigInteger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="storage_location")
 * @ORM\Entity()
 *
 * @OA\Schema(type="object", schema="StorageLocation")
 *
 * @AuditLog\Auditable
 * @AppAssert\StorageLocation()
 */
class StorageLocation
{
    use Traits\TruncateStrings;

    public const TYPE_BACKUP = 'backup';
    public const TYPE_STATION_MEDIA = 'station_media';
    public const TYPE_STATION_RECORDINGS = 'station_recordings';

    public const ADAPTER_LOCAL = 'local';
    public const ADAPTER_S3 = 's3';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="type", type="string", length=50)
     *
     * @Assert\Choice(choices={
     *     StorageLocation::TYPE_BACKUP,
     *     StorageLocation::TYPE_STATION_MEDIA,
     *     StorageLocation::TYPE_STATION_RECORDINGS
     * })
     * @OA\Property(example="station_media")
     * @var string The type of storage location.
     */
    protected $type;

    /**
     * @ORM\Column(name="adapter", type="string", length=50)
     *
     * @Assert\Choice(choices={StorageLocation::ADAPTER_LOCAL, StorageLocation::ADAPTER_S3})
     * @OA\Property(example="local")
     * @var string The storage adapter to use for this location.
     */
    protected $adapter = self::ADAPTER_LOCAL;

    /**
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="/var/azuracast/stations/azuratest_radio/media")
     * @var string|null The local path, if the local adapter is used, or path prefix for S3/remote adapters.
     */
    protected $path;

    /**
     * @ORM\Column(name="s3_credential_key", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="your-key-here")
     * @var string|null The credential key for S3 adapters.
     */
    protected $s3CredentialKey;

    /**
     * @ORM\Column(name="s3_credential_secret", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="your-secret-here")
     * @var string|null The credential secret for S3 adapters.
     */
    protected $s3CredentialSecret;

    /**
     * @ORM\Column(name="s3_region", type="string", length=150, nullable=true)
     *
     * @OA\Property(example="your-region")
     * @var string|null The region for S3 adapters.
     */
    protected $s3Region;

    /**
     * @ORM\Column(name="s3_version", type="string", length=150, nullable=true)
     *
     * @OA\Property(example="latest")
     * @var string|null The API version for S3 adapters.
     */
    protected $s3Version = 'latest';

    /**
     * @ORM\Column(name="s3_bucket", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="your-bucket-name")
     * @var string|null The S3 bucket name for S3 adapters.
     */
    protected $s3Bucket = null;

    /**
     * @ORM\Column(name="s3_endpoint", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="https://your-region.digitaloceanspaces.com")
     * @var string|null The optional custom S3 endpoint S3 adapters.
     */
    protected $s3Endpoint = null;

    /**
     * @ORM\Column(name="storage_quota", type="bigint", nullable=true)
     *
     * @OA\Property(example="50 GB")
     * @var string|null
     */
    protected $storageQuota;

    /**
     * @OA\Property(example="50000000000")
     * @var string|null
     */
    protected $storageQuotaBytes;

    /**
     * @ORM\Column(name="storage_used", type="bigint", nullable=true)
     *
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example="1 GB")
     * @var string|null
     */
    protected $storageUsed;

    /**
     * @OA\Property(example="1000000000")
     * @var string|null
     */
    protected $storageUsedBytes;

    /**
     * @ORM\OneToMany(targetEntity="StationMedia", mappedBy="storage_location")
     * @var Collection|StationMedia[]
     */
    protected $media;

    public function __construct(string $type, string $adapter)
    {
        $this->type = $type;
        $this->adapter = $adapter;

        $this->media = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function applyPath(?string $suffix = null): string
    {
        $suffix = (null !== $suffix)
            ? '/' . ltrim($suffix, '/')
            : '';

        return $this->path . $suffix;
    }

    public function setPath(?string $path): void
    {
        $this->path = $this->truncateString($path, 255);
    }

    public function getS3CredentialKey(): ?string
    {
        return $this->s3CredentialKey;
    }

    public function setS3CredentialKey(?string $s3CredentialKey): void
    {
        $this->s3CredentialKey = $this->truncateString($s3CredentialKey, 255);
    }

    public function getS3CredentialSecret(): ?string
    {
        return $this->s3CredentialSecret;
    }

    public function setS3CredentialSecret(?string $s3CredentialSecret): void
    {
        $this->s3CredentialSecret = $this->truncateString($s3CredentialSecret, 255);
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
        $this->s3Endpoint = $this->truncateString($s3Endpoint, 255);
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
     * @param BigInteger|string|null $storageQuota
     */
    public function setStorageQuota($storageQuota): void
    {
        $storageQuota = (string)Quota::convertFromReadableSize($storageQuota);
        $this->storageQuota = !empty($storageQuota) ? $storageQuota : null;
    }

    public function getStorageQuotaBytes(): ?BigInteger
    {
        $size = $this->storageQuota;

        return (null !== $size)
            ? BigInteger::of($size)
            : null;
    }

    public function getStorageUsed(): ?string
    {
        $raw_size = $this->getStorageUsedBytes();
        return Quota::getReadableSize($raw_size);
    }

    /**
     * @param BigInteger|string|null $storageUsed
     */
    public function setStorageUsed($storageUsed): void
    {
        $storageUsed = (string)Quota::convertFromReadableSize($storageUsed);
        $this->storageUsed = !empty($storageUsed) ? $storageUsed : null;
    }

    public function getStorageUsedBytes(): BigInteger
    {
        $size = $this->storageUsed;

        if (null === $size) {
            return BigInteger::zero();
        }

        return BigInteger::of($size);
    }

    /**
     * Increment the current used storage total.
     *
     * @param BigInteger|string|int $newStorageAmount
     */
    public function addStorageUsed($newStorageAmount): void
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
     * @param BigInteger|string|int $amountToRemove
     */
    public function removeStorageUsed($amountToRemove): void
    {
        if (empty($amountToRemove)) {
            return;
        }

        $currentStorageUsed = $this->getStorageUsedBytes();
        $storageUsed = $currentStorageUsed->minus($amountToRemove);
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
            $totalSpace = BigInteger::of(disk_total_space($localPath));

            if (null === $quota || $quota->isGreaterThan($totalSpace)) {
                return $totalSpace;
            }
        } elseif (null !== $quota) {
            return $quota;
        }

        return null;
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
     * @return StationMedia[]|Collection
     */
    public function getMedia()
    {
        return $this->media;
    }

    public function getUri(?string $suffix = null): string
    {
        $path = $this->applyPath($suffix);

        switch ($this->adapter) {
            case self::ADAPTER_S3:
                try {
                    $client = $this->getS3Client();
                    if (empty($path)) {
                        $objectUrl = $client->getObjectUrl($this->s3Bucket, '/');
                        return rtrim($objectUrl, '/');
                    }

                    return $client->getObjectUrl($this->s3Bucket, ltrim($path, '/'));
                } catch (\InvalidArgumentException $e) {
                    return 'Invalid URI (' . $e->getMessage() . ')';
                }
                break;

            case self::ADAPTER_LOCAL:
            default:
                return $path;
        }
    }

    public function validate(): void
    {
        if (self::ADAPTER_S3 === $this->adapter) {
            $client = $this->getS3Client();
            $client->listObjectsV2([
                'Bucket' => $this->s3Bucket,
                'max-keys' => 1,
            ]);
        }

        $adapter = $this->getStorageAdapter();
        $adapter->has('/test');
    }

    public function getStorageAdapter(): AdapterInterface
    {
        switch ($this->adapter) {
            case self::ADAPTER_S3:
                $client = $this->getS3Client();
                return new AwsS3Adapter($client, $this->s3Bucket, $this->path);

            case self::ADAPTER_LOCAL:
            default:
                return new Local($this->path);
        }
    }

    protected function getS3Client(): S3Client
    {
        if (self::ADAPTER_S3 !== $this->adapter) {
            throw new \InvalidArgumentException('This storage location is not using the S3 adapter.');
        }

        $s3Options = array_filter([
            'credentials' => [
                'key' => $this->s3CredentialKey,
                'secret' => $this->s3CredentialSecret,
            ],
            'region' => $this->s3Region,
            'version' => $this->s3Version,
            'endpoint' => $this->s3Endpoint,
        ]);
        return new S3Client($s3Options);
    }

    /**
     * @param Config|array|null $config
     *
     */
    public function getFilesystem($config = null): Filesystem
    {
        return new Filesystem($this->getStorageAdapter(), $config);
    }

    public function __toString(): string
    {
        $adapterNames = [
            self::ADAPTER_LOCAL => 'Local',
            self::ADAPTER_S3 => 'S3',
        ];
        return $adapterNames[$this->adapter] . ': ' . $this->getUri();
    }
}
