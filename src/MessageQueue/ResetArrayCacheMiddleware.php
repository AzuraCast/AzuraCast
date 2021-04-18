<?php

namespace App\MessageQueue;

use Symfony\Component\Cache\ResettableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Contracts\Cache\CacheInterface;

class ResetArrayCacheMiddleware implements EventSubscriberInterface
{
    protected CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
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
