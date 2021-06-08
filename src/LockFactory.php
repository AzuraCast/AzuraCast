<?php

namespace App;

use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\RetryTillSaveStore;

class LockFactory
{
    public function __construct(
        protected Redis $redis,
        protected LoggerInterface $logger
    ) {
    }

    public function createLock(
        string $resource,
        ?float $ttl = 300.0,
        bool $autoRelease = true,
        int $retrySleep = 1000,
        int $retryCount = 30
    ): LockInterface {
        $store = new RedisStore($this->redis);
        $store = new RetryTillSaveStore($store, $retrySleep, $retryCount);
        $store->setLogger($this->logger);

        $lock = new Lock(new Key($this->getPrefixedResourceName($resource)), $store, $ttl, $autoRelease);
        $lock->setLogger($this->logger);

        return $lock;
    }

    public function createAndAcquireLock(
        string $resource,
        ?float $ttl = 300.0,
        bool $autoRelease = true,
        int $retrySleep = 1000,
        int $retryCount = 30,
        bool $force = false
    ): LockInterface|bool {
        $lock = $this->createLock($resource, $ttl, $autoRelease, $retrySleep, $retryCount);

        if ($force) {
            $this->clearQueue($resource);
            try {
                $lock->acquire(true);
            } catch (\Exception) {
                return false;
            }
        } elseif (!$lock->acquire()) {
            return false;
        }

        return $lock;
    }

    public function clearQueue(string $resource): void
    {
        $this->redis->del($this->getPrefixedResourceName($resource));
    }

    protected function getPrefixedResourceName(string $resource): string
    {
        return 'lock_' . $resource;
    }
}
