<?php

declare(strict_types=1);

namespace App\Cache;

use App\Entity\Station;
use App\Entity\StorageLocation;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

final readonly class MediaListCache
{
    public function __construct(private CacheItemPoolInterface $baseCache)
    {
    }

    public function clearCache(
        Station|StorageLocation|string $tagName
    ): void {
        if (!is_string($tagName)) {
            $tagName = self::getTagName($tagName);
        }

        $this->getCacheForTag($tagName)->clear();
    }

    public function getCacheForTag(
        Station|StorageLocation|string $tagName
    ): CacheItemPoolInterface {
        if (!is_string($tagName)) {
            $tagName = self::getTagName($tagName);
        }

        if ($this->baseCache instanceof NamespacedPoolInterface) {
            return $this->baseCache->withSubNamespace($tagName);
        }

        return new ProxyAdapter(
            $this->baseCache,
            $tagName
        );
    }

    public static function getTagName(
        Station|StorageLocation $storageLocation
    ): string {
        if ($storageLocation instanceof Station) {
            return self::getTagName($storageLocation->media_storage_location);
        }

        return sprintf(
            'media_list_%s',
            $storageLocation->id
        );
    }
}
