<?php

declare(strict_types=1);

namespace App\Lock;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\LockFactory as SymfonyLockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\SharedLockInterface;

final class LockFactory extends SymfonyLockFactory
{
    public function __construct(
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

    public function createLock(string $resource, ?float $ttl = 300.0, bool $autoRelease = true): SharedLockInterface
    {
        return parent::createLock($this->getPrefixedResourceName($resource), $ttl, $autoRelease);
    }

    public function createAndAcquireLock(
        string $resource,
        ?float $ttl = 300.0,
        bool $autoRelease = true,
        bool $force = false
    ): SharedLockInterface|false {
        $lock = $this->createLock($resource, $ttl, $autoRelease);

        if ($force) {
            try {
                $lock->release();
                $lock->acquire(true);
            } catch (Exception) {
                return false;
            }
        } elseif (!$lock->acquire()) {
            return false;
        }

        return $lock;
    }

    private function getPrefixedResourceName(string $resource): string
    {
        return 'lock_' . $resource;
    }
}
