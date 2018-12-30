<?php
namespace App;

use Bernard\Consumer;
use Bernard\Message;
use Bernard\Producer;
use Bernard\Queue;
use Bernard\QueueFactory;

class MessageQueue
{
    const GLOBAL_QUEUE_NAME = 'azuracast';

    /** @var QueueFactory */
    protected $queues;

    /** @var Producer */
    protected $producer;

    /** @var Consumer */
    protected $consumer;

    /**
     * @param QueueFactory $queues
     * @param Producer $producer
     * @param Consumer $consumer
     */
    public function __construct(QueueFactory $queues, Producer $producer, Consumer $consumer)
    {
        $this->queues = $queues;
        $this->producer = $producer;
        $this->consumer = $consumer;
    }

    /**
     * @return Queue
     */
    public function getGlobalQueue(): Queue
    {
        return $this->queues->create(self::GLOBAL_QUEUE_NAME);
    }

    /**
     * @return Producer
     */
    public function getProducer(): Producer
    {
        return $this->producer;
    }

    /**
     * @return Consumer
     */
    public function getConsumer(): Consumer
    {
        return $this->consumer;
    }

    /**
     * Produce (send) a message to the queue.
     *
     * @param Message $message
     */
    public function produce(Message $message): void
    {
        $this->producer->produce($message, self::GLOBAL_QUEUE_NAME);
    }

    /**
     * Consume (receive) messages from the queue.
     *
     * @param array $options
     */
    public function consume(array $options = []): void
    {
        $this->consumer->consume($this->queues->create(self::GLOBAL_QUEUE_NAME), $options);
    }
}
