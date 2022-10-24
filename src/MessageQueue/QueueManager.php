<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Pheanstalk\Exception\JobNotFoundException;
use Pheanstalk\Pheanstalk;
use Symfony\Component\Messenger\Bridge\Beanstalkd\Transport\BeanstalkdTransport;
use Symfony\Component\Messenger\Bridge\Beanstalkd\Transport\Connection as MessengerConnection;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class QueueManager extends AbstractQueueManager
{
    public function __construct(
        private readonly Pheanstalk $pheanstalk
    ) {
    }

    public function clearQueue(string $queueName): void
    {
        $pheanstalk = $this->pheanstalk->useTube($queueName);

        while ($job = $pheanstalk->peekReady()) {
            try {
                $pheanstalk->delete($job);
            } catch (JobNotFoundException) {
            }
        }
        while ($job = $pheanstalk->peekBuried()) {
            try {
                $pheanstalk->delete($job);
            } catch (JobNotFoundException) {
            }
        }
        while ($job = $pheanstalk->peekDelayed()) {
            try {
                $pheanstalk->delete($job);
            } catch (JobNotFoundException) {
            }
        }
    }

    public function getTransport(string $queueName): BeanstalkdTransport
    {
        return new BeanstalkdTransport(
            $this->getConnection($queueName),
            new PhpSerializer()
        );
    }

    private function getConnection(string $queueName): MessengerConnection
    {
        return new MessengerConnection(
            [
                'tube_name' => $queueName,
            ],
            $this->pheanstalk
        );
    }

    public function getQueueCount(string $queueName): int
    {
        try {
            return $this->getConnection($queueName)->getMessageCount();
        } catch (TransportException) {
            return 0;
        }
    }
}
