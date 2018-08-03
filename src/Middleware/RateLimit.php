<?php
namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Apply a rate limit for requests on this page and throw an exception if the limit is exceeded.
 */
class RateLimit
{
    /** @var \AzuraCast\RateLimit */
    protected $rate_limit;

    public function __construct(\AzuraCast\RateLimit $rate_limit)
    {
        $this->rate_limit = $rate_limit;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @param string $rl_group
     * @param int $rl_timeout
     * @param int $rl_interval
     * @return Response
     * @throws \AzuraCast\Exception\RateLimitExceeded
     */
    public function __invoke(Request $request, Response $response, $next, $rl_group = 'default', $rl_timeout = 5, $rl_interval = 2): Response
    {
        $this->rate_limit->checkRateLimit($rl_group, $rl_timeout, $rl_interval);

        return $next($request, $response);
    }
}
