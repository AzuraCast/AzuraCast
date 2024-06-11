<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

interface QueueManagerInterface extends SendersLocatorInterface
{
    public function setWorkerName(string $workerName): void;

    public function clearAllQueues(): void;

    public function clearQueue(QueueNames $queue): void;

    public function getTransport(QueueNames $queue): TransportInterface;

    /**
     * @return TransportInterface[]
     */
    public function getTransports(): array;

    public function getQueueCount(QueueNames $queue): int;
}
