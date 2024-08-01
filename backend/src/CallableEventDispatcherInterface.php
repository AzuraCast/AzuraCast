<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface CallableEventDispatcherInterface extends EventDispatcherInterface
{
    /**
     * @param array|class-string $className
     */
    public function addServiceSubscriber(array|string $className): void;

    /**
     * @param array|class-string $className
     */
    public function removeServiceSubscriber(array|string $className): void;

    public function addCallableListener(
        string $eventName,
        string $className,
        ?string $method = '__invoke',
        int $priority = 0
    ): void;

    public function removeCallableListener(
        string $eventName,
        string $className,
        ?string $method = '__invoke'
    ): void;
}
