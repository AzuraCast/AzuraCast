<?php

declare(strict_types=1);

namespace App;

use Slim\Interfaces\CallableResolverInterface;

use function is_array;
use function is_string;

class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher
{
    public function __construct(
        protected CallableResolverInterface $callableResolver
    ) {
        parent::__construct();
    }

    /**
     * @param array|class-string $className
     */
    public function addServiceSubscriber(array|string $className): void
    {
        if (is_array($className)) {
            foreach ($className as $service) {
                $this->addServiceSubscriber($service);
            }
            return;
        }

        foreach ($className::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addCallableListener(
                    $eventName,
                    $className,
                    $params
                );
            } elseif (is_string($params[0])) {
                $this->addCallableListener(
                    $eventName,
                    $className,
                    $params[0],
                    $params[1] ?? 0
                );
            } else {
                foreach ($params as $listener) {
                    $this->addCallableListener(
                        $eventName,
                        $className,
                        $listener[0],
                        $listener[1] ?? 0
                    );
                }
            }
        }
    }

    /**
     * @param array|class-string $className
     */
    public function removeServiceSubscriber(array|string $className): void
    {
        if (is_array($className)) {
            foreach ($className as $service) {
                $this->removeServiceSubscriber($service);
            }
            return;
        }

        foreach ($className::getSubscribedEvents() as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeCallableListener(
                        $eventName,
                        $className,
                        $listener[0]
                    );
                }
            } else {
                $this->removeCallableListener(
                    $eventName,
                    $className,
                    is_string($params) ? $params : $params[0]
                );
            }
        }
    }

    public function addCallableListener(
        string $eventName,
        string $className,
        ?string $method = '__invoke',
        int $priority = 0
    ): void {
        $this->addListener(
            $eventName,
            $this->getCallable($className, $method),
            $priority
        );
    }

    public function removeCallableListener(
        string $eventName,
        string $className,
        ?string $method = '__invoke'
    ): void {
        $this->removeListener(
            $eventName,
            $this->getCallable($className, $method)
        );
    }

    protected function getCallable(
        string $className,
        ?string $method = '__invoke'
    ): DeferredCallable {
        return new DeferredCallable($className . ':' . $method, $this->callableResolver);
    }
}
