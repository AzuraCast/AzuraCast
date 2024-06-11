<?php

declare(strict_types=1);

namespace App\MessageQueue;

use App\Lock\LockFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

final class HandleUniqueMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected LockFactory $lockFactory
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (
            $envelope->last(ConsumedByWorkerStamp::class) === null
            || !($envelope->getMessage() instanceof UniqueMessageInterface)
        ) {
            return $stack->next()->handle($envelope, $stack);
        }

        /** @var UniqueMessageInterface $message */
        $message = $envelope->getMessage();

        $lock = $this->lockFactory->createLock('message_queue_' . $message->getIdentifier(), $message->getTtl());
        if (!$lock->acquire()) {
            throw new UnrecoverableMessageHandlingException(
                'A queued message matching this one is already being handled.'
            );
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $lock->release();
        }
    }
}
