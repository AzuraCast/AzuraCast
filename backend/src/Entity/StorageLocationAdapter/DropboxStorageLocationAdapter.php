<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\StorageLocation;
use App\Flysystem\Adapter\DropboxAdapter;
use App\Flysystem\Adapter\ExtendedAdapterInterface;
use App\Service\Dropbox\OAuthAdapter;
use Spatie\Dropbox\Client;

final class DropboxStorageLocationAdapter extends AbstractStorageLocationLocationAdapter
{
    public function __construct(
        private readonly OAuthAdapter $oauthAdapter
    ) {
    }

    public function getType(): StorageLocationAdapters
    {
        return StorageLocationAdapters::Dropbox;
    }

    public function getStorageAdapter(): ExtendedAdapterInterface
    {
        $filteredPath = self::filterPath($this->storageLocation->getPath());

        return new DropboxAdapter($this->getClient(), $filteredPath);
    }

    private function getClient(): Client
    {
        return new Client($this->oauthAdapter->withStorageLocation($this->storageLocation));
    }

    public function validate(): void
    {
        $adapter = $this->oauthAdapter->withStorageLocation($this->storageLocation);
        $adapter->setup();

        parent::validate();
    }

    public static function filterPath(string $path): string
    {
        return trim($path, '/');
    }

    public static function getUri(StorageLocation $storageLocation, ?string $suffix = null): string
    {
        $path = self::applyPath($storageLocation->getPath(), $suffix);

        $token = (!empty($storageLocation->getDropboxAuthToken()))
            ? $storageLocation->getDropboxAuthToken()
            : $storageLocation->getDropboxRefreshToken();

        $token = substr(md5($token ?? ''), 0, 10);

        return 'dropbox://' . $token . '/' . ltrim($path, '/');
    }
}
