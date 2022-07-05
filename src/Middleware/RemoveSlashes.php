<?php

declare(strict_types=1);

namespace App\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Remove trailing slash from all URLs when routing.
 */
final class RemoveSlashes implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== '/' && str_ends_with($path, '/')) {
            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath(substr($path, 0, -1));

            /** @var ResponseInterface $response */
            $response = new Response(308);

            return $response->withHeader('Location', (string)$uri);
        }

        return $handler->handle($request);
    }
}
