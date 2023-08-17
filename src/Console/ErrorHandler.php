<?php

declare(strict_types=1);

namespace App\Console;

use App\Container\LoggerAwareTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ErrorHandler implements EventSubscriberInterface
{
    use LoggerAwareTrait;

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
        $commandName = (null !== $command) ? $command->getName() : 'Unknown';

        $exitCode = $event->getExitCode();
        if (0 === $exitCode) {
            return;
        }

        $message = sprintf(
            'Console command `%s` exited with error code %d.',
            $commandName,
            $exitCode
        );
        $this->logger->warning($message);
    }

    public function onError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        $commandName = (null !== $command) ? $command->getName() : 'Unknown';

        $exception = $event->getError();

        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $commandName
        );

        $this->logger->error($message, ['exception' => $exception]);
    }
}
