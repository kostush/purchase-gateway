<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */
    'default'     => env('DB_CONNECTION', 'mysql'),
    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */
    'connections' => [
        'mysql'          => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'database'  => env('DB_DATABASE', 'ng_purchase_gateway'),
            'port'      => env('DB_PORT', '3306'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', 'root'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
        'mysql-readonly' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST_READONLY', env('DB_HOST', '127.0.0.1')),
            'database'  => env('DB_DATABASE', 'ng_purchase_gateway'),
            'port'      => env('DB_PORT_READONLY', env('DB_PORT', '3306')),
            'username'  => env('DB_USERNAME_READONLY', env('DB_USERNAME', 'root')),
            'password'  => env('DB_PASSWORD_READONLY', env('DB_PASSWORD', 'root')),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]
    ],

    /*
     |--------------------------------------------------------------------------
     | Migration Repository Table
     |--------------------------------------------------------------------------
     |
     | This table keeps track of all the migrations that have already run for
     | your application. Using this information, we can determine which of
     | the migrations on disk haven't actually been run in the database.
     |
     */

    'migrations' => base_path('../src/PurchaseGateway/Infrastructure/Persistence/Doctrine/Migrations'),
];
