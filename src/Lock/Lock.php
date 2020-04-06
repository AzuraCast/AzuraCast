<?php
namespace App\Lock;

use malkusch\lock\exception\ExecutionOutsideLockException;
use malkusch\lock\exception\LockAcquireException;
use malkusch\lock\exception\LockReleaseException;
use malkusch\lock\mutex\PHPRedisMutex;
use malkusch\lock\util\Loop;
use Psr\Log\LoggerInterface;
use Redis;

class Lock extends PHPRedisMutex
{
    protected LoggerInterface $logger;

    protected Loop $loop;

    protected int $timeout;

    protected string $key;

    protected float $acquired;

    protected bool $waitForLock = false;

    protected int $waitTimeout = 30;

    public function __construct(
        Redis $redis,
        LoggerInterface $logger,
        string $key,
        int $ttl = 30,
        bool $waitForLock = false,
        ?int $waitTimeout = null
    ) {
        parent::__construct([$redis], $key, $ttl);

        $this->logger = $logger;
        $this->setLogger($logger);

        $this->key = $key;
        $this->timeout = $ttl;
        $this->waitForLock = $waitForLock;
        $this->waitTimeout = $waitTimeout ?? $ttl;
    }

    protected function lock(): void
    {
        if ($this->waitForLock) {
            $this->loop = new Loop($this->waitTimeout);

            $this->loop->execute(function (): void {
                $this->acquired = microtime(true);

                if ($this->acquire($this->key, $this->timeout + 1)) {
                    $this->loop->end();
                }
            });
        } else {
            $this->acquired = microtime(true);

            if (!$this->acquire($this->key, $this->timeout + 1)) {
                throw new LockAcquireException('Failed to acquire the lock.');
            }
        }
    }

    protected function unlock(): void
    {
        $elapsed_time = microtime(true) - $this->acquired;
        if ($elapsed_time > $this->timeout) {
            $e = ExecutionOutsideLockException::create($elapsed_time, $this->timeout);
            $this->logger->error($e->getMessage());
        }

        if (!$this->release($this->key)) {
            throw new LockReleaseException("Failed to release the lock.");
        }
    }

    public function run(callable $code)
    {
        return $this->synchronized($code);
    }
}