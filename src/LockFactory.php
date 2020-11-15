<?php

namespace App;

use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Lock\LockFactory as SymfonyLockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\RetryTillSaveStore;

class LockFactory extends SymfonyLockFactory
{
    protected Redis $redis;

    public function __construct(
        Redis $redis,
        LoggerInterface $logger
    ) {
        $this->redis = $redis;

        $redisStore = new RedisStore($redis);
        $retryStore = new RetryTillSaveStore($redisStore, 1000, 30);
        $retryStore->setLogger($logger);
        parent::__construct($retryStore);

        $this->setLogger($logger);
    }

    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): LockInterface
    {
        return parent::createLock($this->getPrefixedResourceName($resource), $ttl, $autoRelease);
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
