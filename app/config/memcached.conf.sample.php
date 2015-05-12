<?php
/**
 * Memcached configuration and credentials.
 */

return array(
    'servers'   => array(
        'main'      => array(
            // Host or IP to connect to (default: localhost).
            'host'          => 'localhost',
            'port'          => 11211,

            // Priority for cache storage.
            'weight'        => 1,
        ),
    ),

);