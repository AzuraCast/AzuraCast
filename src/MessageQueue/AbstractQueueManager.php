<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Message\AbstractMessage;
use Generator;
use Symfony\Component\Messenger\Envelope;

abstract class AbstractQueueManager implements QueueManagerInterface
{
    protected string $workerName = 'app';

    public function setWorkerName(string $workerName): void
    {
        $this->workerName = $workerName;
    }

    /**
     * @inheritDoc
     */
    public function getSenders(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();

        if (!$message instanceof AbstractMessage) {
            return [
                $this->getTransport(self::QUEUE_NORMAL_PRIORITY),
            ];
        }

        $queue = $message->getQueue();
        return [
            $this->getTransport($queue),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getMessagesInTransport(string $queueName): Generator
    {
        foreach ($this->getTransport($queueName)->get() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof AbstractMessage) {
                yield $message;
            }
        }
    }

    public function getTransports(): array
    {
        $allQueues = self::getAllQueues();

        $transports = [];
        foreach ($allQueues as $queueName) {
            $transports[$queueName] = $this->getTransport($queueName);
        }
        return $transports;
    }

    /**
     * @return string[]
     */
    public static function getAllQueues(): array
    {
        return [
            self::QUEUE_HIGH_PRIORITY,
            self::QUEUE_NORMAL_PRIORITY,
            self::QUEUE_LOW_PRIORITY,
            self::QUEUE_MEDIA,
            self::QUEUE_PODCAST_MEDIA,
        ];
    }
}
