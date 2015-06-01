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
        'path' => DF_INCLUDE_CACHE.DIRECTORY_SEPARATOR,
    ),

    // Redis configuration
    'redis' => array(
        'servers'   => array(
            array(
                'server'    => 'localhost',
                'port'      => 6379, // default: 6379
            ),
        ),

        // 'password'      => '', // Must be commented out to have no authentication
        'database'  => 0,
    ),

    // Memcached configuration
    'memcached' => array(
        'servers'   => array(
            array(
                'server'        => 'localhost',
                'port'          => 11211, // default: 11211
                'weight'        => 1,
            ),
        ),
        'extension' => 'memcached', // Use libmemcached instead of memcache
    ),

);