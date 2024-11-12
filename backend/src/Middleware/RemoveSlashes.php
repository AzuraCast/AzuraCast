<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\HttpFactory;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Remove trailing slash from all URLs when routing.
 */
final class RemoveSlashes extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== '/' && str_ends_with($path, '/')) {
            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath(substr($path, 0, -1));

            $response = (new HttpFactory())->createResponse(308);
            return $response->withHeader('Location', (string)$uri);
        }

        return $handler->handle($request);
    }
}
