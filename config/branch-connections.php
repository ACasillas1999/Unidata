<?php

use Pdo\Mysql;

return [
    'local_connection' => env('BRANCH_LOCAL_CONNECTION', env('DB_CONNECTION', 'sqlite')),
    'active_status' => env('BRANCH_ACTIVE_STATUS', 'active'),
    'connection_prefix' => env('BRANCH_CONNECTION_PREFIX', 'branch_'),

    'template' => [
        'driver' => env('BRANCH_DB_DRIVER', 'mysql'),
        'port' => env('BRANCH_DB_PORT', '3306'),
        'charset' => env('BRANCH_DB_CHARSET', 'utf8mb4'),
        'collation' => env('BRANCH_DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => env('BRANCH_DB_PREFIX', ''),
        'prefix_indexes' => true,
        'strict' => env('BRANCH_DB_STRICT', true),
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_BRANCH_ATTR_SSL_CA'),
        ]) : [],
    ],
];
