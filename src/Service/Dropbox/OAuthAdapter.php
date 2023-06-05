<?php

declare(strict_types=1);

namespace App\Service\Dropbox;

use App\Entity\StorageLocation;
use GuzzleHttp\Exception\ClientException;
use Psr\Cache\CacheItemPoolInterface;
use Spatie\Dropbox\RefreshableTokenProvider;

final class OAuthAdapter implements RefreshableTokenProvider
{
    private StorageLocation $storageLocation;

    public function __construct(
        private readonly CacheItemPoolInterface $psr6Cache
    ) {
    }

    public function withStorageLocation(StorageLocation $storageLocation): self
    {
        $clone = clone $this;
        $clone->setStorageLocation($storageLocation);
        return $clone;
    }

    private function setStorageLocation(StorageLocation $storageLocation): void
    {
        $this->storageLocation = $storageLocation;
    }

    public function setup(): void
    {
        $this->psr6Cache->deleteItem($this->getTokenCacheKey());

        if (!empty($this->storageLocation->getDropboxAuthToken())) {
            // Convert the short-lived auth code into an oauth refresh token.
            $token = $this->getOauthProvider()->getAccessToken(
                'authorization_code',
                [
                    'code' => $this->storageLocation->getDropboxAuthToken(),
                ]
            );

            $this->storageLocation->setDropboxAuthToken(null);
            $this->storageLocation->setDropboxRefreshToken($token->getRefreshToken());
        }
    }

    public function refresh(ClientException $exception): bool
    {
        $this->psr6Cache->deleteItem($this->getTokenCacheKey());
        $this->getToken();

        return true;
    }

    public function getToken(): string
    {
        $cacheKey = $this->getTokenCacheKey();
        $cacheItem = $this->psr6Cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            if (empty($this->storageLocation->getDropboxRefreshToken())) {
                $cacheItem->set($this->storageLocation->getDropboxAuthToken());
            } else {
                // Try to get a new auth token from the refresh token.
                $token = $this->getOauthProvider()->getAccessToken(
                    'refresh_token',
                    [
                        'refresh_token' => $this->storageLocation->getDropboxRefreshToken(),
                    ]
                );

                $cacheItem->set($token->getToken());
            }

            $cacheItem->expiresAfter(600);
            $this->psr6Cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    private function getOauthProvider(): OAuthProvider
    {
        return new OAuthProvider([
            'clientId' => $this->storageLocation->getDropboxAppKey(),
            'clientSecret' => $this->storageLocation->getDropboxAppSecret(),
        ]);
    }

    private function getTokenCacheKey(): string
    {
        return 'storage_location_' . ($this->storageLocation->getId() ?? 'new') . '_auth_token';
    }
}
