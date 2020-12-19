<?php

namespace App\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ErrorHandler implements EventSubscriberInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::TERMINATE => [
                'onTerminate',
            ],
            ConsoleEvents::ERROR => [
                'onError',
            ],
        ];
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();

        $exitCode = $event->getExitCode();
        if (0 === $exitCode) {
            return;
        }

        $message = sprintf(
            'Console command `%s` exited with error code %d.',
            $command->getName(),
            $exitCode
        );
        $this->logger->warning($message);
    }

    public function onError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        $exception = $event->getError();

        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $command->getName()
        );

        $this->logger->error($message, ['exception' => $exception]);
    }
}
