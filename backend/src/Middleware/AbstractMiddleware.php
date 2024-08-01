<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TypeError;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!($request instanceof ServerRequest)) {
            throw new TypeError('Invalid server request.');
        }

        return $this->__invoke($request, $handler);
    }

    abstract public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface;
}
