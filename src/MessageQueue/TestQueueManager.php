<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class TestQueueManager extends AbstractQueueManager
{
    public function clearQueue(QueueNames $queue): void
    {
        // Noop
    }

    public function getTransport(QueueNames $queue): TransportInterface
    {
        return new InMemoryTransport();
    }

    public function getQueueCount(QueueNames $queue): int
    {
        return 0;
    }
}
