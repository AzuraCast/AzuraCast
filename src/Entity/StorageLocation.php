<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Exception\StorageLocationFullException;
use App\Radio\Quota;
use App\Validator\Constraints as AppAssert;
use Aws\S3\S3Client;
use Azura\Files\Adapter\AwsS3\AwsS3Adapter;
use Azura\Files\Adapter\Dropbox\DropboxAdapter;
use Azura\Files\Adapter\ExtendedAdapterInterface;
use Azura\Files\Adapter\Local\LocalFilesystemAdapter;
use Azura\Files\Adapter\LocalAdapterInterface;
use Azura\Files\Adapter\Sftp\SftpAdapter;
use Azura\Files\ExtendedFilesystemInterface;
use Azura\Files\LocalFilesystem;
use Azura\Files\RemoteFilesystem;
use Brick\Math\BigInteger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use RuntimeException;
use Spatie\Dropbox\Client;
use Stringable;

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

    public const DEFAULT_BACKUPS_PATH = '/var/azuracast/backups';

    #[ORM\Column(length: 50)]
    protected string $type;

    #[ORM\Column(length: 50)]
    protected string $adapter;

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

    #[ORM\Column(name: 'sftp_host', length: 255, nullable: true)]
    protected ?string $sftpHost = null;

    #[ORM\Column(name: 'sftp_username', length: 255, nullable: true)]
    protected ?string $sftpUsername = null;

    #[ORM\Column(name: 'sftp_password', length: 255, nullable: true)]
    protected ?string $sftpPassword = null;

    #[ORM\Column(name: 'sftp_port', nullable: true)]
    protected ?int $sftpPort = null;

    #[ORM\Column(name: 'sftp_private_key', type: 'text', nullable: true)]
    protected ?string $sftpPrivateKey = null;

    #[ORM\Column(name: 'sftp_private_key_pass_phrase', length: 255, nullable: true)]
    protected ?string $sftpPrivateKeyPassPhrase = null;

    #[ORM\Column(name: 'storage_quota', type: 'bigint', nullable: true)]
    protected ?string $storageQuota = null;

    #[ORM\Column(name: 'storage_used', type: 'bigint', nullable: true)]
    #[Attributes\AuditIgnore]
    protected ?string $storageUsed = null;

    #[ORM\OneToMany(mappedBy: 'storage_location', targetEntity: StationMedia::class)]
    protected Collection $media;

    public function __construct(
        string|StorageLocationTypes $type,
        string|StorageLocationAdapters $adapter
    ) {
        if (!($type instanceof StorageLocationTypes)) {
            $type = StorageLocationTypes::from($type);
        }
        if (!($adapter instanceof StorageLocationAdapters)) {
            $adapter = StorageLocationAdapters::from($adapter);
        }

        $this->type = $type->value;
        $this->adapter = $adapter->value;

        $this->media = new ArrayCollection();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeEnum(): StorageLocationTypes
    {
        return StorageLocationTypes::from($this->type);
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function getAdapterEnum(): StorageLocationAdapters
    {
        return StorageLocationAdapters::from($this->adapter);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFilteredPath(): string
    {
        return match ($this->getAdapterEnum()) {
            StorageLocationAdapters::S3, StorageLocationAdapters::Dropbox => trim($this->path, '/'),
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

    public function getSftpHost(): ?string
    {
        return $this->sftpHost;
    }

    public function setSftpHost(?string $sftpHost): void
    {
        $this->sftpHost = $sftpHost;
    }

    public function getSftpUsername(): ?string
    {
        return $this->sftpUsername;
    }

    public function setSftpUsername(?string $sftpUsername): void
    {
        $this->sftpUsername = $sftpUsername;
    }

    public function getSftpPassword(): ?string
    {
        return $this->sftpPassword;
    }

    public function setSftpPassword(?string $sftpPassword): void
    {
        $this->sftpPassword = $sftpPassword;
    }

    public function getSftpPort(): ?int
    {
        return $this->sftpPort;
    }

    public function setSftpPort(?int $sftpPort): void
    {
        $this->sftpPort = $sftpPort;
    }

    public function getSftpPrivateKey(): ?string
    {
        return $this->sftpPrivateKey;
    }

    public function setSftpPrivateKey(?string $sftpPrivateKey): void
    {
        $this->sftpPrivateKey = $sftpPrivateKey;
    }

    public function getSftpPrivateKeyPassPhrase(): ?string
    {
        return $this->sftpPrivateKeyPassPhrase;
    }

    public function setSftpPrivateKeyPassPhrase(?string $sftpPrivateKeyPassPhrase): void
    {
        $this->sftpPrivateKeyPassPhrase = $sftpPrivateKeyPassPhrase;
    }

    public function isLocal(): bool
    {
        return $this->getAdapterEnum()->isLocal();
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
        $raw_size = $this->getStorageAvailableBytes();

        return ($raw_size instanceof BigInteger)
            ? Quota::getReadableSize($raw_size)
            : '';
    }

    public function getStorageAvailableBytes(): ?BigInteger
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
        $available = $this->getStorageAvailableBytes();
        if ($available === null) {
            return false;
        }

        $used = $this->getStorageUsedBytes();

        return ($used->compareTo($available) !== -1);
    }

    public function canHoldFile(BigInteger|int|string $size): bool
    {
        if (empty($size)) {
            return true;
        }

        $available = $this->getStorageAvailableBytes();
        if ($available === null) {
            return true;
        }

        $newStorageUsed = $this->getStorageUsedBytes()->plus($size);
        return ($newStorageUsed->compareTo($available) === -1);
    }

    public function errorIfFull(): void
    {
        if ($this->isStorageFull()) {
            throw new StorageLocationFullException();
        }
    }

    public function getStorageUsePercentage(): int
    {
        $storageUsed = $this->getStorageUsedBytes();
        $storageAvailable = $this->getStorageAvailableBytes();

        if (null === $storageAvailable) {
            return 0;
        }

        return Quota::getPercentage($storageUsed, $storageAvailable);
    }

    /**
     * @return Collection<StationMedia>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function getUri(?string $suffix = null): string
    {
        $path = $this->applyPath($suffix);

        return match ($this->getAdapterEnum()) {
            StorageLocationAdapters::S3 => $this->getS3ObjectUri($suffix),
            StorageLocationAdapters::Dropbox => 'dropbox://' . $this->dropboxAuthToken . ltrim($path, '/'),
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
        if (StorageLocationAdapters::S3 === $this->getAdapterEnum()) {
            $client = $this->getS3Client();
            $client->listObjectsV2(
                [
                    'Bucket'   => $this->s3Bucket,
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

        switch ($this->getAdapterEnum()) {
            case StorageLocationAdapters::S3:
                $bucket = $this->s3Bucket;
                if (null === $bucket) {
                    throw new RuntimeException('Amazon S3 bucket is empty.');
                }
                return new AwsS3Adapter($this->getS3Client(), $bucket, $filteredPath);

            case StorageLocationAdapters::Dropbox:
                return new DropboxAdapter($this->getDropboxClient(), $filteredPath);

            case StorageLocationAdapters::Sftp:
                return new SftpAdapter($this->getSftpConnectionProvider(), $filteredPath);

            default:
                return new LocalFilesystemAdapter($filteredPath);
        }
    }

    protected function getS3Client(): S3Client
    {
        if (StorageLocationAdapters::S3 !== $this->getAdapterEnum()) {
            throw new InvalidArgumentException('This storage location is not using the S3 adapter.');
        }

        $s3Options = array_filter(
            [
                'credentials' => [
                    'key'    => $this->s3CredentialKey,
                    'secret' => $this->s3CredentialSecret,
                ],
                'region'      => $this->s3Region,
                'version'     => $this->s3Version,
                'endpoint'    => $this->s3Endpoint,
            ]
        );
        return new S3Client($s3Options);
    }

    protected function getDropboxClient(): Client
    {
        if (StorageLocationAdapters::Dropbox !== $this->getAdapterEnum()) {
            throw new InvalidArgumentException('This storage location is not using the Dropbox adapter.');
        }

        return new Client($this->dropboxAuthToken);
    }

    protected function getSftpConnectionProvider(): SftpConnectionProvider
    {
        if (StorageLocationAdapters::Sftp !== $this->getAdapterEnum()) {
            throw new InvalidArgumentException('This storage location is not using the SFTP adapter.');
        }

        return new SftpConnectionProvider(
            $this->sftpHost ?? '',
            $this->sftpUsername ?? '',
            $this->sftpPassword,
            $this->sftpPrivateKey,
            $this->sftpPrivateKeyPassPhrase,
            $this->sftpPort ?? 22
        );
    }

    public function getFilesystem(): ExtendedFilesystemInterface
    {
        $adapter = $this->getStorageAdapter();

        return ($adapter instanceof LocalAdapterInterface)
            ? new LocalFilesystem(
                adapter: $adapter,
                visibilityConverter: new PortableVisibilityConverter(defaultForDirectories: Visibility::PUBLIC)
            )
            : new RemoteFilesystem($adapter);
    }

    public function __toString(): string
    {
        return $this->getAdapterEnum()->getName() . ': ' . $this->getUri();
    }
}
