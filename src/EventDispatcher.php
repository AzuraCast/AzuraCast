<?php
namespace App;

use Slim\Interfaces\CallableResolverInterface;
use function is_array;
use function is_string;

class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher
{
    protected CallableResolverInterface $callableResolver;

    public function __construct(CallableResolverInterface $callableResolver)
    {
        parent::__construct();

        $this->callableResolver = $callableResolver;
    }

    public function addServiceSubscriber($class_name): void
    {
        if (is_array($class_name)) {
            foreach ($class_name as $service) {
                $this->addServiceSubscriber($service);
            }
            return;
        }

        foreach ($class_name::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, $this->getCallable($class_name, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, $this->getCallable($class_name, $params[0]),
                    $params[1] ?? 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, $this->getCallable($class_name, $listener[0]),
                        $listener[1] ?? 0);
                }
            }
        }
    }

    public function removeServiceSubscriber($class_name): void
    {
        if (is_array($class_name)) {
            foreach ($class_name as $service) {
                $this->removeServiceSubscriber($service);
            }
            return;
        }

        foreach ($class_name::getSubscribedEvents() as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, $this->getCallable($class_name, $listener[0]));
                }
            } else {
                $this->removeListener($eventName,
                    $this->getCallable($class_name, is_string($params) ? $params : $params[0]));
            }
        }
    }

    protected function getCallable($class_name, $method): DeferredCallable
    {
        return new DeferredCallable($class_name . ':' . $method, $this->callableResolver);
    }

}
