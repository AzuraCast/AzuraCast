<?php

declare(strict_types=1);

namespace App\Service;

use Fuse\Fuse;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

final class FuseSearch
{
    public const int DEFAULT_TTL = 60;

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function search(
        ?string $searchPhrase,
        array $records,
        array $options,
        string $cacheKey,
        int $cacheTtl = self::DEFAULT_TTL,
        bool $flushCache = false
    ): array {
        $fuse = $this->buildSearch($records, $options, $cacheKey, $cacheTtl, $flushCache);
        return array_column($fuse->search($searchPhrase), 'item');
    }

    public function buildSearch(
        array $records,
        array $options,
        string $cacheKey,
        int $cacheTtl = self::DEFAULT_TTL,
        bool $flushCache = false
    ): Fuse {
        $keys = $options['keys'] ?? null;
        if (null === $keys) {
            throw new InvalidArgumentException('No keys provided for search.');
        }

        $keysHash = substr(md5(json_encode($keys, JSON_THROW_ON_ERROR)), 0, 6);
        $cacheKey .= '_fuse_' . $keysHash;

        if (!$flushCache && $this->cache->has($cacheKey)) {
            $indexData = $this->cache->get($cacheKey);
            $index = Fuse::parseIndex(
                $indexData,
                $options
            );
        } else {
            $index = Fuse::createIndex($keys, $records, $options);

            $this->cache->set(
                $cacheKey,
                $index->jsonSerialize(),
                $cacheTtl
            );
        }

        return new Fuse($records, $options, $index);
    }
}
