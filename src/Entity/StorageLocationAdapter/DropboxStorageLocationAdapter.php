<?php

declare(strict_types=1);

namespace App\Entity\StorageLocationAdapter;

use App\Entity\Enums\StorageLocationAdapters;
use Azura\Files\Adapter\Dropbox\DropboxAdapter;
use Azura\Files\Adapter\ExtendedAdapterInterface;
use Spatie\Dropbox\Client;

final class DropboxStorageLocationAdapter extends AbstractStorageLocationLocationAdapter
{
    public function getType(): StorageLocationAdapters
    {
        return StorageLocationAdapters::Dropbox;
    }

    public static function filterPath(string $path): string
    {
        return trim($path, '/');
    }

    public function getUri(?string $suffix = null): string
    {
        $path = $this->applyPath($suffix);
        $appKey = $this->storageLocation->getDropboxAppKey();

        $uriPrefix = (!empty($appKey))
            ? $appKey
            : $this->storageLocation->getDropboxAuthToken();

        return 'dropbox://' . $uriPrefix . '/' . ltrim($path, '/');
    }

    public function getStorageAdapter(): ExtendedAdapterInterface
    {
        $filteredPath = self::filterPath($this->storageLocation->getPath());

        return new DropboxAdapter($this->getClient(), $filteredPath);
    }

    private function getClient(): Client
    {
        $appKey = $this->storageLocation->getDropboxAppKey();
        $appSecret = $this->storageLocation->getDropboxAppSecret();
        $authToken = $this->storageLocation->getDropboxAuthToken();

        $creds = (!empty($appKey) && !empty($appSecret))
            ? [$appKey, $appSecret]
            : $authToken;

        return new Client($creds);
    }
}
