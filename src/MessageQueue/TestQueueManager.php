<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TestQueueManager extends AbstractQueueManager
{
    public function clearQueue(string $queueName): void
    {
        return; // Noop
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
