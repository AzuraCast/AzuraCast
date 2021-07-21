<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Message\AbstractMessage;
use Generator;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

interface QueueManagerInterface extends SendersLocatorInterface
{
    public const QUEUE_HIGH_PRIORITY = 'high_priority';
    public const QUEUE_NORMAL_PRIORITY = 'normal_priority';
    public const QUEUE_LOW_PRIORITY = 'low_priority';
    public const QUEUE_MEDIA = 'media';
    public const QUEUE_PODCAST_MEDIA = 'podcast_media';

    public function setWorkerName(string $workerName): void;

    public function clearQueue(string $queueName): void;

    public function getTransport(string $queueName): TransportInterface;

    /**
     * @param string $queueName
     *
     * @return Generator<AbstractMessage>
     */
    public function getMessagesInTransport(string $queueName): Generator;

    /**
     * @return TransportInterface[]
     */
    public function getTransports(): array;

    public function getQueueCount(string $queueName): int;

    public static function getAllQueues(): array;
}
