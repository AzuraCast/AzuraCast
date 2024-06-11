<?php

declare(strict_types=1);

namespace App\MessageQueue;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Contracts\Cache\CacheInterface;

final class ResetArrayCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CacheInterface $cache
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

    public function resetArrayCache(): void
    {
        if ($this->cache instanceof ArrayAdapter) {
            $this->cache->reset();
        }
    }
}
