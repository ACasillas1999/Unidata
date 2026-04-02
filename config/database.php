<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'local_mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_LOCAL_URL'),
            'host' => env('DB_LOCAL_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_LOCAL_PORT', env('DB_PORT', '3306')),
            'database' => env('DB_LOCAL_DATABASE', env('DB_DATABASE', 'laravel')),
            'username' => env('DB_LOCAL_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('DB_LOCAL_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_LOCAL_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('DB_LOCAL_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('DB_LOCAL_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => env('DB_LOCAL_STRICT', true),
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_LOCAL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'aiesa' => [
            'driver' => 'mysql',
            'host' => env('DB_AIESA_HOST', '192.168.40.1'),
            'port' => '3307',
            'database' => env('DB_AIESA_DATABASE', 'aiesa'),
            'username' => env('DB_AIESA_USERNAME', 'consulta'),
            'password' => env('DB_AIESA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'cedis' => [
            'driver' => 'mysql',
            'host' => env('DB_CEDIS_HOST', '192.168.100.20'),
            'port' => '3307',
            'database' => env('DB_CEDIS_DATABASE', 'cedis'),
            'username' => env('DB_CEDIS_USERNAME', 'consulta'),
            'password' => env('DB_CEDIS_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'washington' => [
            'driver' => 'mysql',
            'host' => env('DB_WASHINGTON_HOST', '192.168.150.1'),
            'port' => '3307',
            'database' => env('DB_WASHINGTON_DATABASE', 'washington'),
            'username' => env('DB_WASHINGTON_USERNAME', 'consulta'),
            'password' => env('DB_WASHINGTON_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'deasa' => [
            'driver' => 'mysql',
            'host' => env('DB_DEASA_HOST', '192.168.20.1'),
            'port' => '3307',
            'database' => env('DB_DEASA_DATABASE', 'deasa'),
            'username' => env('DB_DEASA_USERNAME', 'consulta'),
            'password' => env('DB_DEASA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'dimegsa' => [
            'driver' => 'mysql',
            'host' => env('DB_DIMEGSA_HOST', '192.168.10.1'),
            'port' => '3307',
            'database' => env('DB_DIMEGSA_DATABASE', 'dimegsa'),
            'username' => env('DB_DIMEGSA_USERNAME', 'consulta'),
            'password' => env('DB_DIMEGSA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'fesa' => [
            'driver' => 'mysql',
            'host' => env('DB_FESA_HOST', '192.168.50.1'),
            'port' => '3307',
            'database' => env('DB_FESA_DATABASE', 'fesa'),
            'username' => env('DB_FESA_USERNAME', 'consulta'),
            'password' => env('DB_FESA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'gabsa' => [
            'driver' => 'mysql',
            'host' => env('DB_GABSA_HOST', '192.168.1.1'),
            'port' => '3307',
            'database' => env('DB_GABSA_DATABASE', 'gabsa'),
            'username' => env('DB_GABSA_USERNAME', 'consulta'),
            'password' => env('DB_GABSA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'ilu' => [
            'driver' => 'mysql',
            'host' => env('DB_ILU_HOST', '192.168.2.1'),
            'port' => '3307',
            'database' => env('DB_ILU_DATABASE', 'ilu'),
            'username' => env('DB_ILU_USERNAME', 'consulta'),
            'password' => env('DB_ILU_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'queretaro' => [
            'driver' => 'mysql',
            'host' => env('DB_QUERETARO_HOST', '192.168.140.1'),
            'port' => '3307',
            'database' => env('DB_QUERETARO_DATABASE', 'queretaro'),
            'username' => env('DB_QUERETARO_USERNAME', 'consulta'),
            'password' => env('DB_QUERETARO_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'segsa' => [
            'driver' => 'mysql',
            'host' => env('DB_SEGSA_HOST', '192.168.30.1'),
            'port' => '3307',
            'database' => env('DB_SEGSA_DATABASE', 'segsa'),
            'username' => env('DB_SEGSA_USERNAME', 'consulta'),
            'password' => env('DB_SEGSA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'tapatia' => [
            'driver' => 'mysql',
            'host' => env('DB_TAPATIA_HOST', '192.168.70.1'),
            'port' => '3307',
            'database' => env('DB_TAPATIA_DATABASE', 'tapatia'),
            'username' => env('DB_TAPATIA_USERNAME', 'consulta'),
            'password' => env('DB_TAPATIA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'vallarta' => [
            'driver' => 'mysql',
            'host' => env('DB_VALLARTA_HOST', '192.168.120.1'),
            'port' => '3307',
            'database' => env('DB_VALLARTA_DATABASE', 'vallarta'),
            'username' => env('DB_VALLARTA_USERNAME', 'consulta'),
            'password' => env('DB_VALLARTA_PASSWORD', 'ctl3026'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => 'root',
            'password' => 'qry3026',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
