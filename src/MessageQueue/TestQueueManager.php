<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class TestQueueManager extends AbstractQueueManager
{
    public function clearQueue(string $queueName): void
    {
        // Noop
    }

    public function getTransport(string $queueName): TransportInterface
    {
        return new InMemoryTransport();
    }

    public function getQueueCount(string $queueName): int
    {
        return 0;
    }
}
