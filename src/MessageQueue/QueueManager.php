<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Pheanstalk\Pheanstalk;
use Symfony\Component\Messenger\Bridge\Beanstalkd\Transport\BeanstalkdTransport;
use Symfony\Component\Messenger\Bridge\Beanstalkd\Transport\Connection as MessengerConnection;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

class QueueManager extends AbstractQueueManager
{
    public function __construct(
        protected Pheanstalk $pheanstalk
    ) {
    }

    public function clearQueue(string $queueName): void
    {
        $pheanstalk = $this->pheanstalk->useTube($queueName);

        while ($job = $pheanstalk->reserve()) {
            $pheanstalk->delete($job);
        }
    }

    public function getTransport(string $queueName): BeanstalkdTransport
    {
        return new BeanstalkdTransport(
            $this->getConnection($queueName),
            new PhpSerializer()
        );
    }

    protected function getConnection(string $queueName): MessengerConnection
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
