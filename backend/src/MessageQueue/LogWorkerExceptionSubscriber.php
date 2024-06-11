<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Container\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

final class LogWorkerExceptionSubscriber implements EventSubscriberInterface
{
    use LoggerAwareTrait;

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
