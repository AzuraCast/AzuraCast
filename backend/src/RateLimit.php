<?php

declare(strict_types=1);

namespace App;

use App\Cache\CacheNamespace;
use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Entity\Repository\SettingsRepository;
use App\Exception\Http\RateLimitExceededException;
use App\Http\ServerRequest;
use App\Lock\LockFactory;
use PhpIP\IP;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

final class RateLimit
{
    use EnvironmentAwareTrait;
    use LoggerAwareTrait;

    private CacheItemPoolInterface $psr6Cache;

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly SettingsRepository $settingsRepo,
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->psr6Cache = CacheNamespace::RateLimit->withNamespace($cacheItemPool);
    }

    /**
     * @param ServerRequest $request
     * @param string $groupName
     * @param int $interval
     * @param int $limit
     *
     * @throws RateLimitExceededException
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

        $ipObj = IP::create($ip);
        if ($ipObj->isReserved()) {
            $this->logger->warning(
                sprintf(
                    'User IP (%s) is internal; IP detection may not be properly configured. '
                    . 'Falling back to global rate limits.',
                    $ip,
                )
            );

            if (!$this->checkRateLimit($groupName, 'global', $interval, $limit * 10)) {
                throw new RateLimitExceededException($request);
            }
        } else {
            $ipKey = str_replace([':', '.'], '_', $ip);

            if (!$this->checkRateLimit($groupName, $ipKey, $interval, $limit)) {
                throw new RateLimitExceededException($request);
            }
        }
    }

    public function checkRateLimit(
        string $groupName,
        string $key,
        int $interval = 5,
        int $limit = 2
    ): bool {
        $cacheStore = new CacheStorage($this->psr6Cache);

        $config = [
            'id' => 'ratelimit.' . $groupName,
            'policy' => 'sliding_window',
            'interval' => $interval . ' seconds',
            'limit' => $limit,
        ];

        try {
            $rateLimiterFactory = new RateLimiterFactory($config, $cacheStore, $this->lockFactory);
            $rateLimiter = $rateLimiterFactory->create($key);

            return $rateLimiter->consume()->isAccepted();
        } catch (LockConflictedException | LockAcquiringException) {
            return false;
        }
    }
}
