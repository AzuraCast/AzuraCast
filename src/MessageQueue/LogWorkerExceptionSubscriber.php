<?php

namespace App\MessageQueue;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class LogWorkerExceptionSubscriber implements EventSubscriberInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkerMessageFailedEvent::class => 'logError',
        ];
    }

    public function logError(WorkerMessageFailedEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
        ]);
    }
}
