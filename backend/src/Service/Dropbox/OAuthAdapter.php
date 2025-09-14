<?php

declare(strict_types=1);

namespace App\Service\Dropbox;

use App\Entity\StorageLocation;
use App\Utilities\Types;
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

        if (!empty($this->storageLocation->dropboxAuthToken)) {
            // Convert the short-lived auth code into an oauth refresh token.
            $token = $this->getOauthProvider()->getAccessToken(
                'authorization_code',
                [
                    'code' => $this->storageLocation->dropboxAuthToken,
                ]
            );

            $this->storageLocation->dropboxAuthToken = null;
            $this->storageLocation->dropboxRefreshToken = $token->getRefreshToken();
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
            if (empty($this->storageLocation->dropboxRefreshToken)) {
                $cacheItem->set($this->storageLocation->dropboxAuthToken);
            } else {
                // Try to get a new auth token from the refresh token.
                $token = $this->getOauthProvider()->getAccessToken(
                    'refresh_token',
                    [
                        'refresh_token' => $this->storageLocation->dropboxRefreshToken,
                    ]
                );

                $cacheItem->set($token->getToken());
            }

            $cacheItem->expiresAfter(600);
            $this->psr6Cache->save($cacheItem);
        }

        return Types::string($cacheItem->get());
    }

    private function getOauthProvider(): OAuthProvider
    {
        return new OAuthProvider([
            'clientId' => $this->storageLocation->dropboxAppKey,
            'clientSecret' => $this->storageLocation->dropboxAppSecret,
        ]);
    }

    private function getTokenCacheKey(): string
    {
        return 'storage_location_' . ($this->storageLocation->id ?? 'new') . '_auth_token';
    }
}
