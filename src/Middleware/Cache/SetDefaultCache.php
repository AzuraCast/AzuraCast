<?php

declare(strict_types=1);

namespace App\Middleware\Cache;

use App\Http\ServerRequest;
use App\Middleware\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SetDefaultCache extends AbstractMiddleware
{
    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->hasCacheLifetime($response)) {
            return $response->withHeader('Pragma', 'no-cache')
                ->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', 0))
                ->withHeader('Cache-Control', 'private, no-cache, no-store')
                ->withHeader('X-Accel-Buffering', 'no') // Nginx
                ->withHeader('X-Accel-Expires', '0'); // CloudFlare/nginx
        }

        return $response;
    }

    private function hasCacheLifetime(ResponseInterface $response): bool
    {
        if ($response->hasHeader('Pragma')) {
            return (!str_contains($response->getHeaderLine('Pragma'), 'no-cache'));
        }

        return (!str_contains($response->getHeaderLine('Cache-Control'), 'no-cache'));
    }
}
