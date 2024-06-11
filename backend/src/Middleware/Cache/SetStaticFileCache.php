<?php

declare(strict_types=1);

namespace App\Middleware\Cache;

use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

class SetStaticFileCache extends SetCache
{
    public function __construct(
        protected string $longCacheParam = 'timestamp'
    ) {
        parent::__construct(0, 0);
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $routeArgs = RouteContext::fromRequest($request)->getRoute()?->getArguments();
        $hasLongCacheParam = !empty($routeArgs[$this->longCacheParam]);

        return ($hasLongCacheParam)
            ? $this->responseWithCacheLifetime($response, self::CACHE_ONE_YEAR, self::CACHE_ONE_DAY)
            : $this->responseWithCacheLifetime($response, self::CACHE_ONE_HOUR, self::CACHE_ONE_MINUTE);
    }
}
