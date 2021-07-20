<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Message\AbstractMessage;
use Generator;
use Pheanstalk\Pheanstalk;
use Symfony\Component\Messenger\Bridge\Beanstalkd\Transport\BeanstalkdTransport;
use Symfony\Component\Messenger\Bridge\Beanstalkd\Transport\Connection as MessengerConnection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class QueueManager implements SendersLocatorInterface
{
    public const QUEUE_HIGH_PRIORITY = 'high_priority';
    public const QUEUE_NORMAL_PRIORITY = 'normal_priority';
    public const QUEUE_LOW_PRIORITY = 'low_priority';
    public const QUEUE_MEDIA = 'media';
    public const QUEUE_PODCAST_MEDIA = 'podcast_media';

    protected string $workerName = 'app';

    public function __construct(
        protected Pheanstalk $pheanstalk
    ) {
    }

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

    public function getConnection(string $queueName): MessengerConnection
    {
        return new MessengerConnection(
            [
                'tube_name' => $queueName,
            ],
            $this->pheanstalk
        );
    }

    public function getTransport(string $queueName): BeanstalkdTransport
    {
        return new BeanstalkdTransport(
            $this->getConnection($queueName),
            new Serializer()
        );
    }

    /**
     * @param string $queueName
     *
     * @return Generator<AbstractMessage>
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

    /**
     * @return AmazonSqsTransport[]
     */
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
     * @return MessengerConnection[]
     */
    public function getConnections(): array
    {
        $allQueues = self::getAllQueues();

        $connections = [];
        foreach ($allQueues as $queueName) {
            $connections[$queueName] = $this->getConnection($queueName);
        }
        return $connections;
    }

    public function getQueueCount(string $queueName): int
    {
        return $this->getConnection($queueName)->getMessageCount();
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
