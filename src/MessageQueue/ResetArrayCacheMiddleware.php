<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Symfony\Component\Cache\ResettableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Contracts\Cache\CacheInterface;

class ResetArrayCacheMiddleware implements EventSubscriberInterface
{
    public function __construct(
        protected CacheInterface $cache
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => [
                ['resetArrayCache', -100],
            ],
        ];
    }

    public function resetArrayCache(WorkerMessageReceivedEvent $event): void
    {
        if ($this->cache instanceof ResettableInterface) {
            $this->cache->reset();
        }
    }
}
