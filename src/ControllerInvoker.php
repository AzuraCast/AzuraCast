<?php

declare(strict_types=1);

namespace App;

use Invoker\InvokerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class ControllerInvoker implements InvocationStrategyInterface
{
    public function __construct(
        protected InvokerInterface $invoker
    ) {
    }

    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        $parameters = [
            'request' => $request,
            'response' => $response,
        ];
        $parameters += $routeArguments;

        return $this->invoker->call($callable, $parameters);
    }
}
