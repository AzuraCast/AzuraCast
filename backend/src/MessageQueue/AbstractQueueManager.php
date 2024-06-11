<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Message\AbstractMessage;
use Symfony\Component\Messenger\Envelope;

abstract class AbstractQueueManager implements QueueManagerInterface
{
    protected string $workerName = 'app';

    public function setWorkerName(string $workerName): void
    {
        $this->workerName = $workerName;
    }

    public function clearAllQueues(): void
    {
        foreach (QueueNames::cases() as $queue) {
            $this->clearQueue($queue);
        }
    }

    /**
     * @inheritDoc
     */
    public function getSenders(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();

        if (!$message instanceof AbstractMessage) {
            return [
                QueueNames::NormalPriority->value => $this->getTransport(QueueNames::NormalPriority),
            ];
        }

        $queue = $message->getQueue();
        return [
            $queue->value => $this->getTransport($queue),
        ];
    }

    public function getTransports(): array
    {
        $transports = [];
        foreach (QueueNames::cases() as $queue) {
            $transports[$queue->value] = $this->getTransport($queue);
        }
        return $transports;
    }
}
