<?php
namespace App;

use Slim\Interfaces\CallableResolverInterface;
use function is_array;
use function is_string;

class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher
{
    /** @var CallableResolverInterface */
    protected $callableResolver;

    public function __construct(CallableResolverInterface $callableResolver)
    {
        parent::__construct();

        $this->callableResolver = $callableResolver;
    }

    public function addServiceSubscriber($class_name)
    {
        if (is_array($class_name)) {
            foreach ($class_name as $service) {
                $this->addServiceSubscriber($service);
            }
            return;
        }

        foreach ($class_name::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, $this->_getCallable($class_name, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, $this->_getCallable($class_name, $params[0]),
                    isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, $this->_getCallable($class_name, $listener[0]),
                        isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    public function removeServiceSubscriber($class_name)
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
                    $this->removeListener($eventName, $this->_getCallable($class_name, $listener[0]));
                }
            } else {
                $this->removeListener($eventName,
                    $this->_getCallable($class_name, is_string($params) ? $params : $params[0]));
            }
        }
    }

    protected function _getCallable($class_name, $method)
    {
        return new DeferredCallable($class_name . ':' . $method, $this->callableResolver);
    }

}
