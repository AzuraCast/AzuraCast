<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Station;
use Psr\Cache\CacheItemPoolInterface;

final class SaveCacheCommand extends AbstractCommand
{
    public function __construct(
        private readonly CacheItemPoolInterface $psr6Cache
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): bool {
        if (!$asAutoDj) {
            return false;
        }

        $cacheKey = $payload['cache_key'] ?? null;
        $data = $payload['data'] ?? null;

        if (empty($cacheKey) || empty($data)) {
            return false;
        }

        $cacheItem = $this->psr6Cache->getItem($cacheKey);

        $cacheItem->set($data);
        $cacheItem->expiresAfter(86400);

        $this->psr6Cache->save($cacheItem);

        return true;
    }
}
