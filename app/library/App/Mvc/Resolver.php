<?php
namespace App\Mvc;

use RuntimeException;
use Interop\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched.
 */
class Resolver implements CallableResolverInterface
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->di = $container;
    }

    public function resolve($toResolve)
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve))
        {
            list($module, $controller, $action) = explode(':', $toResolve);

            $class = '\\Modules\\'.ucfirst($module).'\\Controllers\\'.ucfirst($controller).'Controller';
            if (!class_exists($class))
                throw new RuntimeException(sprintf('Callable %s does not exist', $class));

            $resolved = [new $class($this->di, $module, $controller, $action), 'dispatch'];
        }

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
            ));
        }

        return $resolved;
    }
}
