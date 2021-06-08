<?php

namespace App\MessageQueue;

use App\Message\AbstractMessage;
use Doctrine\DBAL\Connection;
use Generator;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as MessengerConnection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class QueueManager implements SendersLocatorInterface
{
    public const QUEUE_HIGH_PRIORITY = 'high_priority';
    public const QUEUE_NORMAL_PRIORITY = 'normal_priority';
    public const QUEUE_LOW_PRIORITY = 'low_priority';
    public const QUEUE_MEDIA = 'media';
    public const QUEUE_PODCAST_MEDIA = 'podcast_media';

    protected string $workerName = 'app';

    public function __construct(
        protected Connection $db
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
                'table_name' => 'messenger_messages',
                'queue_name' => $queueName,
                'auto_setup' => false,
            ],
            $this->db
        );
    }

    public function getTransport(string $queueName): DoctrineTransport
    {
        return new DoctrineTransport(
            $this->getConnection($queueName),
            new PhpSerializer()
        );
    }

    /**
     * @param string $queueName
     *
     * @return Generator|AbstractMessage[]
     */
    public function getMessagesInTransport(string $queueName): Generator
    {
        foreach ($this->getTransport($queueName)->all() as $envelope) {
            yield $envelope->getMessage();
        }
    }

    /**
     * @return DoctrineTransport[]
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
