<?php

namespace App;

use App\Http\ServerRequest;
use Redis;

class RateLimit
{
    protected Redis $redis;

    protected Environment $environment;

    public function __construct(Redis $redis, Environment $environment)
    {
        $this->redis = $redis;
        $this->environment = $environment;
    }

    /**
     * @param ServerRequest $request
     * @param string $groupName
     * @param int $timeout
     * @param int $interval
     *
     * @throws Exception\RateLimitExceededException
     */
    public function checkRequestRateLimit(
        ServerRequest $request,
        string $groupName,
        int $timeout = 5,
        int $interval = 2
    ): void {
        if ($this->environment->isTesting() || $this->environment->isCli()) {
            return;
        }

        $ip = $request->getIp();
        $cacheName = sprintf(
            '%s.%s',
            $groupName,
            str_replace(':', '.', $ip)
        );

        $this->checkRateLimit($cacheName, $timeout, $interval);
    }

    /**
     * @param string $groupName
     * @param int $timeout
     * @param int $interval
     *
     * @throws Exception\RateLimitExceededException
     */
    public function checkRateLimit(
        string $groupName,
        int $timeout = 5,
        int $interval = 2
    ): void {
        $cacheName = sprintf(
            'rate_limit.%s',
            $groupName
        );

        $result = $this->redis->get($cacheName);

        if ($result !== false) {
            if ((int)$result + 1 > $interval) {
                throw new Exception\RateLimitExceededException();
            }

            $this->redis->incr($cacheName);
        } else {
            $this->redis->setex($cacheName, $timeout, 1);
        }
    }
}
