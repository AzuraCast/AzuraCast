<?php

declare(strict_types=1);

namespace App;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Repository\SettingsRepository;
use App\Http\ServerRequest;
use App\Lock\LockFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

final class RateLimit
{
    use EnvironmentAwareTrait;

    private CacheItemPoolInterface $psr6Cache;

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly SettingsRepository $settingsRepo,
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->psr6Cache = new ProxyAdapter($cacheItemPool, 'ratelimit.');
    }

    /**
     * @param ServerRequest $request
     * @param string $groupName
     * @param int $interval
     * @param int $limit
     *
     * @throws Exception\RateLimitExceededException
     */
    public function checkRequestRateLimit(
        ServerRequest $request,
        string $groupName,
        int $interval = 5,
        int $limit = 2
    ): void {
        if ($this->environment->isTesting() || $this->environment->isCli()) {
            return;
        }

        $ip = $this->settingsRepo->readSettings()->getIp($request);

        $ipKey = str_replace([':', '.'], '_', $ip);
        $this->checkRateLimit($groupName, $ipKey, $interval, $limit);
    }

    /**
     * @param string $groupName
     * @param string $key
     * @param int $interval
     * @param int $limit
     *
     * @throws Exception\RateLimitExceededException
     */
    public function checkRateLimit(
        string $groupName,
        string $key,
        int $interval = 5,
        int $limit = 2
    ): void {
        $cacheStore = new CacheStorage($this->psr6Cache);

        $config = [
            'id' => 'ratelimit.' . $groupName,
            'policy' => 'sliding_window',
            'interval' => $interval . ' seconds',
            'limit' => $limit,
        ];

        $rateLimiterFactory = new RateLimiterFactory($config, $cacheStore, $this->lockFactory);
        $rateLimiter = $rateLimiterFactory->create($key);

        if (!$rateLimiter->consume()->isAccepted()) {
            throw new Exception\RateLimitExceededException();
        }
    }
}
