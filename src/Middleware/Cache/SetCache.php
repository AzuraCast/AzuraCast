<?php

declare(strict_types=1);

namespace App\Middleware\Cache;

use App\Http\ServerRequest;
use App\Middleware\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetCache extends AbstractMiddleware
{
    public const CACHE_ONE_MINUTE = 60;
    public const CACHE_ONE_HOUR = 3600;
    public const CACHE_ONE_DAY = 86400;
    public const CACHE_ONE_MONTH = 2592000;
    public const CACHE_ONE_YEAR = 31536000;

    public function __construct(
        protected int $browserLifetime,
        protected ?int $serverLifetime = null
    ) {
        $this->serverLifetime ??= $this->browserLifetime;
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $this->responseWithCacheLifetime(
            $response,
            $this->browserLifetime,
            $this->serverLifetime
        );
    }

    protected function responseWithCacheLifetime(
        ResponseInterface $response,
        int $browserLifetime,
        ?int $serverLifetime = null
    ): ResponseInterface {
        $serverLifetime ??= $browserLifetime;

        return $response->withoutHeader('Pragma')
            ->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $browserLifetime))
            ->withHeader('Cache-Control', 'public, max-age=' . $browserLifetime)
            ->withHeader('X-Accel-Buffering', 'yes') // Nginx
            ->withHeader('X-Accel-Expires', (string)$serverLifetime); // CloudFlare/nginx
    }
}
