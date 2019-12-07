<?php
namespace App;

use App\Message\AbstractDelayedMessage;
use Bernard\BernardEvents;
use Bernard\Consumer;
use Bernard\Event\EnvelopeEvent;
use Bernard\Event\RejectEnvelopeEvent;
use Bernard\Message;
use Bernard\Producer;
use Bernard\Queue;
use Bernard\QueueFactory;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MessageQueue implements EventSubscriberInterface
{
    public const GLOBAL_QUEUE_NAME = 'azuracast';

    protected QueueFactory $queues;

    protected Producer $producer;

    protected Consumer $consumer;

    protected Logger $logger;

    protected EntityManager $em;

    public function __construct(
        QueueFactory $queues,
        Producer $producer,
        Consumer $consumer,
        Logger $logger,
        EntityManager $em
    ) {
        $this->queues = $queues;
        $this->producer = $producer;
        $this->consumer = $consumer;
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            BernardEvents::PING => [
                ['checkEntityManager', -10],
            ],
            BernardEvents::PRODUCE => [
                ['logProduce', -5],
            ],
            BernardEvents::INVOKE => [
                ['logInvoke', -5],
                ['handleDelay', 0],
            ],
            BernardEvents::REJECT => [
                ['logReject', -5],
            ],
        ];
    }

    public function getGlobalQueue(): Queue
    {
        return $this->queues->create(self::GLOBAL_QUEUE_NAME);
    }

    public function getProducer(): Producer
    {
        return $this->producer;
    }

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

    /**
     * @param EnvelopeEvent $e
     */
    public function handleDelay(EnvelopeEvent $e): void
    {
        $message = $e->getEnvelope()->getMessage();

        if ($message instanceof AbstractDelayedMessage) {
            $this->logger->debug(sprintf(
                'Delaying queued message %s by %d microseconds.',
                $message->getName(),
                $message->delay
            ));

            $delay_us = $message->delay;
            usleep($delay_us);
        }
    }

    public function checkEntityManager(): void
    {
        // Shut the process manager down if the entity manager isn't open.
        if (!$this->em->isOpen()) {
            exit;
        }

        // Clear the EM before running any new tasks.
        $this->em->clear();
    }

    /**
     * @param EnvelopeEvent $event
     */
    public function logProduce(EnvelopeEvent $event): void
    {
        $this->logger->info(sprintf(
            'New message of type %s added to queue.', $event->getEnvelope()->getMessage()->getName()
        ));
    }

    /**
     * @param EnvelopeEvent $event
     */
    public function logInvoke(EnvelopeEvent $event): void
    {
        $this->logger->info(sprintf(
            'Handling message of type %s.', $event->getEnvelope()->getMessage()->getName()
        ));
    }

    /**
     * @param RejectEnvelopeEvent $event
     */
    public function logReject(RejectEnvelopeEvent $event): void
    {
        $this->logger->error(sprintf(
            'Exception when processing message type %s: %s',
            $event->getEnvelope()->getMessage()->getName(),
            $event->getException()->getMessage()
        ));
    }
}
