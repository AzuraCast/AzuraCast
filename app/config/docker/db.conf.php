<?php
/**
 * Database configuration and credentials.
 */

return [
    // Backend driver to use with the database.
    'driver' => 'pdo_mysql',

    // Host or IP to connect to (default: localhost).
    'host' => 'mariadb',

    // Name of the primary application database.
    'dbname' => 'azuracast',

    // Username for the database user with read/write access to the above database.
    'user' => 'azuracast',

    // Password for the user account specified above.
    'password' => 'azur4c457',

    // Character set.
    'charset' => 'utf8',

    // Other options to send to the PDO adapter for the database.
    'driverOptions' => [
        1002 => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ],
];