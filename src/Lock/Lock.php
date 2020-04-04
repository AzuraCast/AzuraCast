<?php
namespace App\Lock;

use App\Exception\LockAlreadyExistsException;
use App\Exception\LockWaitExpiredException;
use Psr\SimpleCache\CacheInterface;

class Lock
{
    protected CacheInterface $cache;

    protected string $identifier;

    protected int $timeout = 30;

    protected bool $waitForLock = false;

    protected int $waitInterval = 1;

    protected int $waitTimeout = 30;

    public function __construct(CacheInterface $cache, string $identifier, int $timeout)
    {
        $this->cache = $cache;
        $this->identifier = 'lock_' . $identifier;
        $this->timeout = $timeout;
    }

    public function waitForLock(int $interval = 1, int $timeout = 30): void
    {
        $this->waitForLock = true;
        $this->waitInterval = $interval;
        $this->waitTimeout = $timeout;
    }

    public function isLocked(): bool
    {
        return $this->cache->has($this->identifier);
    }

    public function unlock(): void
    {
        $this->cache->delete($this->identifier);
    }

    public function lock(bool $force = false): void
    {
        if (!$force && $this->isLocked()) {
            throw new LockAlreadyExistsException(sprintf('Lock already exists for identifier "%s".',
                $this->identifier));
        }

        $this->cache->set($this->identifier, time(), $this->timeout);
    }

    public function run(callable $task, bool $force = false)
    {
        if ($this->waitForLock && !$force) {
            $elapsedTime = 0;
            while (true) {
                if ($elapsedTime > 0) {
                    sleep($this->waitInterval);
                }

                $elapsedTime += $this->waitInterval;

                try {
                    $this->lock();
                    break;
                } catch (LockAlreadyExistsException $e) {
                    if ($elapsedTime > $this->waitTimeout) {
                        throw new LockWaitExpiredException;
                    }
                }
            }
        } else {
            $this->lock($force);
        }

        try {
            return $task();
        } finally {
            $this->unlock();
        }
    }
}