<?php

use Illuminate\Support\Str;

$mysqlOptions = [];

if (extension_loaded('pdo_mysql')) {
    $sslCaAttribute = defined('Pdo\\Mysql::ATTR_SSL_CA')
        ? constant('Pdo\\Mysql::ATTR_SSL_CA')
        : constant('PDO::MYSQL_ATTR_SSL_CA');
    $sslVerifyServerCertificateAttribute = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
        ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
        : constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT');

    $mysqlOptions = [
        // For Aiven MySQL - enable SSL without local CA file
        $sslCaAttribute => '/etc/ssl/certs/ca-certificates.crt',
        $sslVerifyServerCertificateAttribute => false,
    ];
}

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'simakdbplus'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => $mysqlOptions,
        ],
    ],

    'migrations' => 'migrations',
];
