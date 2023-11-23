<?php

use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventCommandHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Lumen's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Lumen. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => env('QUEUE_TABLE', 'jobs'),
            'queue' => 'default',
            'retry_after' => 90,
        ]
    ],

    'connection' => [
        'host'    => env('QUEUE_HOST', 'rabbitmq'),
        'user'    => env('QUEUE_USER', 'guest'),
        'pass'    => env('QUEUE_PASS', 'guest'),
        'options' => [
            'vhost'                                                                       => env('QUEUE_VHOST', '/'),
            'queue_number_' . CreateLegacyImportEventCommandHandler::WORKER_NAME          => env('QUEUE_NUMBER_LEGACY', '1'),
            'queue_number_' . CreateMemberProfileEnrichedEventCommandHandler::WORKER_NAME => env('QUEUE_NUMBER_MEMBER_PROFILE', '1')
        ],
        'connectionTimeout' => (int) env('QUEUE_CONNECTION_TIMEOUT', 300000) // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
    ],

];
