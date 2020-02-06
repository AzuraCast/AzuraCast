<?php

return [

    // Cache
    Psr\Cache\CacheItemPoolInterface::class => function (App\Settings $settings, Psr\Container\ContainerInterface $di) {
        // Never use the Redis cache for CLI commands, as the CLI commands are where
        // the Redis cache gets flushed, so this will lead to a race condition that can't
        // be solved within the application.
        return $settings->enableRedis() && !$settings->isCli()
            ? new Cache\Adapter\Redis\RedisCachePool($di->get(Redis::class))
            : new Cache\Adapter\PHPArray\ArrayCachePool;
    },
    Psr\SimpleCache\CacheInterface::class => DI\get(Psr\Cache\CacheItemPoolInterface::class),

    // Doctrine cache
    Doctrine\Common\Cache\Cache::class => function (Psr\Cache\CacheItemPoolInterface $cachePool) {
        return new Cache\Bridge\Doctrine\DoctrineCacheBridge(new Cache\Prefixed\PrefixedCachePool($cachePool,
            'doctrine|'));
    },

    // Session save handler middleware
    Mezzio\Session\SessionPersistenceInterface::class => function (Cache\Adapter\Redis\RedisCachePool $redisPool) {
        return new Mezzio\Session\Cache\CacheSessionPersistence(
            new Cache\Prefixed\PrefixedCachePool($redisPool, 'session|'),
            'app_session',
            '/',
            'nocache',
            43200,
            time()
        );
    },

    // Redis cache
    Redis::class => function (App\Settings $settings) {
        $redis_host = $settings[App\Settings::IS_DOCKER] ? 'redis' : 'localhost';

        $redis = new Redis();
        $redis->connect($redis_host, 6379, 15);
        $redis->select(1);

        return $redis;
    },

];