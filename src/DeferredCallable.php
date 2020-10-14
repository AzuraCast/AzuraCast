<?php

/**
 * Deferred Callable
 * Used from:
 * Slim Framework (https://slimframework.com)
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace App;

use Slim\Interfaces\CallableResolverInterface;

class DeferredCallable
{
    /**
     * @var callable|string
     */
    protected $callable;

    protected ?CallableResolverInterface $callableResolver;

    /**
     * @param callable|string $callable
     * @param CallableResolverInterface|null $resolver
     */
    public function __construct($callable, ?CallableResolverInterface $resolver = null)
    {
        $this->callable = $callable;
        $this->callableResolver = $resolver;
    }

    /**
     * @return mixed
     */
    public function __invoke(...$args)
    {
        /** @var callable $callable */
        $callable = $this->callable;
        if ($this->callableResolver) {
            $callable = $this->callableResolver->resolve($callable);
        }

        return $callable(...$args);
    }
}
