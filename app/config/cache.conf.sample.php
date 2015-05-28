<?php
/**
 * Backend cache configuration.
 */

return array(
    // Valid options are:
    // ephemeral - Uses in-memory cache that expires at page request.
    // memcached - Uses libmemcached and 'memcached' settings below.
    // redis - Uses phpredis and 'redis' settings below.
    // file - Uses flat-file storage and 'file' settings below.
    'cache' => 'ephemeral',

    // Flatfile configuration
    'file' => array(
        'cacheDir' => DF_INCLUDE_CACHE.DIRECTORY_SEPARATOR,
    ),

    // Redis configuration
    'redis' => array(
        'host'      => 'localhost',
        'port'      => 6379, // default: 6379
        'auth'      => '',
        'persistent' => false
    ),

    // Memcached configuration
    'memcached' => array(
        'servers'   => array(
            'main'      => array(
                // Host or IP to connect to (default: localhost, port 11211).
                'host'          => 'localhost',
                'port'          => 11211,

                // Priority for cache storage.
                'weight'        => 1,
            ),
        ),
        'client' => array(),
    ),

);