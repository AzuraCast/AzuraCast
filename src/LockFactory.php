<?php

declare(strict_types=1);

namespace App;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\LockFactory as SymfonyLockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\RetryTillSaveStore;

class LockFactory extends SymfonyLockFactory
{
    public function __construct(
        protected Environment $environment,
        PersistingStoreInterface $lockStore,
        LoggerInterface $logger
    ) {
        if (!$lockStore instanceof BlockingStoreInterface) {
            $lockStore = new RetryTillSaveStore($lockStore, 30, 1000);
            $lockStore->setLogger($logger);
        }

        parent::__construct($lockStore);
        $this->setLogger($logger);
    }

    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): LockInterface
    {
        return parent::createLock($this->getPrefixedResourceName($resource), $ttl, $autoRelease);
    }

    public function createAndAcquireLock(
        string $resource,
        ?float $ttl = 300.0,
        bool $autoRelease = true,
        bool $force = false
    ): LockInterface|false {
        $lock = $this->createLock($resource, $ttl, $autoRelease);

        if ($force) {
            try {
                $lock->release();
                $lock->acquire(true);
            } catch (\Exception) {
                return false;
            }
        } elseif (!$lock->acquire()) {
            return false;
        }

        return $lock;
    }

    protected function getPrefixedResourceName(string $resource): string
    {
        return 'lock_' . $resource;
    }
}
