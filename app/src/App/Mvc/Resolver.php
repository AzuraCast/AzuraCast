<?php
namespace App\Mvc;

use Interop\Container\ContainerInterface;
use RuntimeException;
use Slim\CallableResolver;
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

    /**
     * @param mixed $toResolve
     * @return callable
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function resolve($toResolve)
    {
        if (\is_callable($toResolve)) {
            return $toResolve;
        }

        // Check for array formatting of [callable, arg1, arg2, arg3]
        // Call the callable function at position 0 with optional arguments 1, 2... merged in.
        if (\is_array($toResolve)) {

            $callable_name = array_shift($toResolve);
            $callable = $this->resolve($callable_name);

            $this->assertCallable($callable);

            return function() use ($callable, $toResolve) {
                return \call_user_func_array($callable, array_merge(func_get_args(), $toResolve));
            };

        }

        if (\is_string($toResolve)) {

            // Check for Slim notation pattern of "class:method"
            if (preg_match(CallableResolver::CALLABLE_PATTERN, $toResolve, $matches)) {
                $resolved = $this->resolveCallable($matches[1], $matches[2]);
                $this->assertCallable($resolved);

                return $resolved;
            }

            // Resolve in MVC controller format
            // "foo:bar:baz" -> \Controller\Foo\BarController::bazAction (via dispatch())
            if (strpos($toResolve, ':') !== false) {
                list($module, $controller, $action) = explode(':', $toResolve);

                $class = '\\Controller\\' . ucfirst($module) . '\\' . ucfirst($controller) . 'Controller';
                if (!class_exists($class)) {
                    throw new RuntimeException(sprintf('Controller %s does not exist', $class));
                }

                return function ($request, $response, $args) use ($class, $module, $controller, $action) {


                    $controller = new $class($this->di, $module, $controller, $action);
                    return $controller->dispatch($request, $response, $args);
                };
            }
        }

        $resolved = $this->resolveCallable($toResolve);
        $this->assertCallable($resolved);
        return $resolved;
    }

    /**
     * Check if string is something in the DIC
     * that's callable or is a class name which has an __invoke() method.
     *
     * @param string $class
     * @param string $method
     * @param array $args Additional arguments to pass to the constructor.
     * @return callable
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function resolveCallable($class, $method = '__invoke', $args = array())
    {
        if ($this->di->has($class)) {
            return [$this->di->get($class), $method];
        }

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Callable %s does not exist', $class));
        }

        return [new $class($this->di, $args), $method];
    }

    /**
     * @param Callable $callable
     *
     * @throws \RuntimeException if the callable is not resolvable
     */
    protected function assertCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($callable) || is_object($callable) ? json_encode($callable) : $callable
            ));
        }
    }
}
