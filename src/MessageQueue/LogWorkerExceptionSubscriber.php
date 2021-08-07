<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class LogWorkerExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
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
