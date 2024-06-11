<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Lock;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\BlockingStoreInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\PersistingStoreInterface;

use const PHP_INT_MAX;

/**
 * Copied from Symfony 5.x as it was deprecated in 6.x with no suitable replacement.
 *
 * RetryTillSaveStore is a PersistingStoreInterface implementation which decorate a non blocking
 * PersistingStoreInterface to provide a blocking storage.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class RetryTillSaveStore implements BlockingStoreInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param int $retrySleep Duration in ms between 2 retry
     * @param int $retryCount Maximum amount of retry
     */
    public function __construct(
        private readonly PersistingStoreInterface $decorated,
        private readonly int $retrySleep = 100,
        private readonly int $retryCount = PHP_INT_MAX
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function save(Key $key): void
    {
        $this->decorated->save($key);
    }

    /**
     * {@inheritdoc}
     */
    public function waitAndSave(Key $key): void
    {
        $retry = 0;
        $sleepRandomness = (int)($this->retrySleep / 10);
        do {
            try {
                $this->decorated->save($key);

                return;
            } catch (LockConflictedException) {
                usleep(($this->retrySleep + random_int(-$sleepRandomness, $sleepRandomness)) * 1000);
            }
        } while (++$retry < $this->retryCount);

        $this->logger?->warning(
            'Failed to store the "{resource}" lock. Abort after {retry} retry.',
            ['resource' => $key, 'retry' => $retry]
        );

        throw new LockConflictedException();
    }

    /**
     * {@inheritdoc}
     */
    public function putOffExpiration(Key $key, float $ttl): void
    {
        $this->decorated->putOffExpiration($key, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Key $key): void
    {
        $this->decorated->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Key $key): bool
    {
        return $this->decorated->exists($key);
    }
}
