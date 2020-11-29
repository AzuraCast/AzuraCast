<?php

namespace App\MessageQueue;

use App\Message\AbstractMessage;
use Redis;
use Symfony\Component\Messenger\Bridge\Redis\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

class QueueManager implements SendersLocatorInterface
{
    public const QUEUE_HIGH_PRIORITY = 'high_priority';
    public const QUEUE_NORMAL_PRIORITY = 'normal_priority';
    public const QUEUE_LOW_PRIORITY = 'low_priority';
    public const QUEUE_MEDIA = 'media';

    public Redis $redis;

    /** @var Connection[] */
    public array $connections = [];

    public string $workerName = 'app';

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
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
            return [];
        }

        $queue = $message->getQueue();
        return [
            $this->getTransport($queue),
        ];
    }

    public function getConnection(string $queueName): Connection
    {
        $cacheName = $queueName . '_' . $this->workerName;

        if (!isset($this->connections[$cacheName])) {
            $this->connections[$cacheName] = new Connection(
                [
                    'stream' => 'messages_' . $queueName,
                    'consumer' => $this->workerName,
                    'delete_after_ack' => true,
                    'redeliver_timeout' => 43200,
                    'claim_interval' => 86400,
                ],
                array_filter([
                    'host' => $this->redis->getHost(),
                    'port' => $this->redis->getPort(),
                    'auth' => $this->redis->getAuth(),
                ])
            );
        }

        return $this->connections[$cacheName];
    }

    public function getTransport(string $queueName): RedisTransport
    {
        return new RedisTransport($this->getConnection($queueName));
    }

    /**
     * @return RedisTransport[]
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
     * @return Connection[]
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

    public function clearQueue(string $queueName): void
    {
        $connection = $this->getConnection($queueName);

        $connection->cleanup();
        $connection->setup();
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
        ];
    }
}
