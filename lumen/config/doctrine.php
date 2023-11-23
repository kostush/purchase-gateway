<?php

use ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine;

return [

    /*
    |--------------------------------------------------------------------------
    | Entities Mangers
    |--------------------------------------------------------------------------
    |
    | Configure your Entities Managers here. You can set a different connection
    | and driver per manager and configure events and filters. Change the
    | paths setting to the appropriate path and replace App namespace
    | by your own namespace.
    |
    | Available meta drivers: fluent|annotations|yaml|xml|config|static_php|php
    |
    | Available connections: mysql|oracle|pgsql|sqlite|sqlsrv
    | (Connections can be configured in the database config)
    |
    | --> Warning: Proxy auto generation should only be enabled in dev!
    |
    */
    'managers'                   => [
        'default' => [
            'dev'           => env('APP_DEBUG', false),
            'meta'          => env('DOCTRINE_METADATA', 'xml'),
            'connection'    => env('DB_CONNECTION', 'mysql'),
            'namespaces'    => [],
            'paths'         => [
                base_path('../src/PurchaseGateway/Infrastructure/Persistence/Doctrine/Mapping'),
                base_path('../vendor/probiller-ng/projection-library/src/Infrastructure/Persistence/Doctrine/Mapping'),
            ],
            'repository'    => \Doctrine\ORM\EntityRepository::class,
            'proxies'       => [
                'namespace'     => false,
                'path'          => storage_path('proxies'),
                'auto_generate' => env('DOCTRINE_PROXY_AUTOGENERATE', false)
            ],
            /*
            |--------------------------------------------------------------------------
            | Doctrine events
            |--------------------------------------------------------------------------
            |
            | The listener array expects the key to be a Doctrine event
            | e.g. Doctrine\ORM\Events::onFlush
            |
            */
            'events'        => [
                'listeners'   => [],
                'subscribers' => []
            ],
            'filters'       => [],
            /*
            |--------------------------------------------------------------------------
            | Doctrine mapping types
            |--------------------------------------------------------------------------
            |
            | Link a Database Type to a Local Doctrine Type
            |
            | Using 'enum' => 'string' is the same of:
            | $doctrineManager->extendAll(function (\Doctrine\ORM\Configuration $configuration,
            |         \Doctrine\DBAL\Connection $connection,
            |         \Doctrine\Common\EventManager $eventManager) {
            |     $connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            | });
            |
            | References:
            | http://doctrine-orm.readthedocs.org/en/latest/cookbook/custom-mapping-types.html
            | http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html#custom-mapping-types
            | http://doctrine-orm.readthedocs.org/en/latest/cookbook/advanced-field-value-conversion-using-custom-mapping-types.html
            | http://doctrine-orm.readthedocs.org/en/latest/reference/basic-mapping.html#reference-mapping-types
            | http://symfony.com/doc/current/cookbook/doctrine/dbal.html#registering-custom-mapping-types-in-the-schematool
            |--------------------------------------------------------------------------
            */
            'mapping_types' => [
                //'enum' => 'string'
            ]
        ],
        'mysql-readonly' => [
            'dev'           => env('APP_DEBUG', false),
            'meta'          => env('DOCTRINE_METADATA', 'xml'),
            'connection'    => env('DB_CONNECTION_READONLY', env('DB_CONNECTION', 'mysql')),
            'namespaces'    => [],
            'paths'         => [
                base_path('../src/PurchaseGateway/Infrastructure/Persistence/Doctrine/Mapping'),
                base_path('../vendor/probiller-ng/projection-library/src/Infrastructure/Persistence/Doctrine/Mapping'),
            ],
            'repository'    => \Doctrine\ORM\EntityRepository::class,
            'proxies'       => [
                'namespace'     => false,
                'path'          => storage_path('proxies'),
                'auto_generate' => env('DOCTRINE_PROXY_AUTOGENERATE', false)
            ],
            'events'        => [
                'listeners'   => [],
                'subscribers' => []
            ],
            'filters'       => [],
            'mapping_types' => []
        ]
    ],
    /*
    |--------------------------------------------------------------------------
    | Doctrine Extensions
    |--------------------------------------------------------------------------
    |
    | Enable/disable Doctrine Extensions by adding or removing them from the list
    |
    | If you want to require custom extensions you will have to require
    | laravel-doctrine/extensions in your composer.json
    |
    */
    'extensions'                 => [
        //LaravelDoctrine\ORM\Extensions\TablePrefix\TablePrefixExtension::class,
        //LaravelDoctrine\Extensions\Timestamps\TimestampableExtension::class,
        //LaravelDoctrine\Extensions\SoftDeletes\SoftDeleteableExtension::class,
        //LaravelDoctrine\Extensions\Sluggable\SluggableExtension::class,
        //LaravelDoctrine\Extensions\Sortable\SortableExtension::class,
        //LaravelDoctrine\Extensions\Tree\TreeExtension::class,
        //LaravelDoctrine\Extensions\Loggable\LoggableExtension::class,
        //LaravelDoctrine\Extensions\Blameable\BlameableExtension::class,
        //LaravelDoctrine\Extensions\IpTraceable\IpTraceableExtension::class,
        //LaravelDoctrine\Extensions\Translatable\TranslatableExtension::class
    ],
    /*
    |--------------------------------------------------------------------------
    | Doctrine custom types
    |--------------------------------------------------------------------------
    |
    | Create a custom or override a Doctrine Type
    |--------------------------------------------------------------------------
    */
    'custom_types'               => [
        'json'                                => LaravelDoctrine\ORM\Types\Json::class,
        'MemberId'                            => Doctrine\Mapping\Types\DoctrineMemberId::class,
        'SubscriptionId'                      => Doctrine\Mapping\Types\DoctrineSubscriptionId::class,
        'ItemId'                              => Doctrine\Mapping\Types\DoctrineItemId::class,
        'BundleId'                            => Doctrine\Mapping\Types\DoctrineBundleId::class,
        'PurchaseId'                          => Doctrine\Mapping\Types\DoctrinePurchaseId::class,
        'AddonId'                             => Doctrine\Mapping\Types\DoctrineAddonId::class,
        'AddonType'                           => Doctrine\Mapping\Types\DoctrineAddonType::class,
        'TransactionId'                       => Doctrine\Mapping\Types\DoctrineTransactionId::class,
        'SessionId'                           => Doctrine\Mapping\Types\DoctrineSessionId::class,
        'SubscriptionInfoJsonSerializer'      => Doctrine\Mapping\Types\SubscriptionInfoJsonSerializer::class,
        'AddonCollectionJsonSerializer'       => Doctrine\Mapping\Types\AddonCollectionJsonSerializer::class,
        'TransactionCollectionJsonSerializer' => Doctrine\Mapping\Types\TransactionCollectionJsonSerializer::class,
        'SiteId'                              => Doctrine\Mapping\Types\DoctrineSiteId::class,
        'BusinessGroupId'                     => Doctrine\Mapping\Types\DoctrineBusinessGroupId::class,
        'DoctrineServiceCollection'           => Doctrine\Mapping\Types\DoctrineServiceCollection::class,
        'DoctrinePublicKeyCollection'         => Doctrine\Mapping\Types\DoctrinePublicKeyCollection::class,
        'DoctrineSitePublicKeyCollection'     => Doctrine\Mapping\Types\DoctrineSitePublicKeyCollection::class,
        \Ramsey\Uuid\Doctrine\UuidType::NAME  => \Ramsey\Uuid\Doctrine\UuidType::class
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom datetime functions
    |--------------------------------------------------------------------------
    */
    'custom_datetime_functions'  => [],
    /*
    |--------------------------------------------------------------------------
    | DQL custom numeric functions
    |--------------------------------------------------------------------------
    */
    'custom_numeric_functions'   => [],
    /*
    |--------------------------------------------------------------------------
    | DQL custom string functions
    |--------------------------------------------------------------------------
    */
    'custom_string_functions'    => [],
    /*
    |--------------------------------------------------------------------------
    | Register custom hydrators
    |--------------------------------------------------------------------------
    */
    'custom_hydration_modes'     => [
        // e.g. 'hydrationModeName' => MyHydrator::class,
    ],
    /*
    |--------------------------------------------------------------------------
    | Enable query logging with laravel file logging,
    | debugbar, clockwork or an own implementation.
    | Setting it to false, will disable logging
    |
    | Available:
    | - LaravelDoctrine\ORM\Loggers\LaravelDebugbarLogger
    | - LaravelDoctrine\ORM\Loggers\ClockworkLogger
    | - LaravelDoctrine\ORM\Loggers\FileLogger
    |--------------------------------------------------------------------------
    */
    'logger'                     => env('DOCTRINE_LOGGER', false),
    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configure meta-data, query and result caching here.
    | Optionally you can enable second level caching.
    |
    | Available: apc|array|file|memcached|redis|void
    |
    */
    'cache'                      => [
        'second_level' => false,
        'default'      => env('DOCTRINE_CACHE', 'array'),
        'namespace'    => null,
        'metadata'     => [
            'driver'    => env('DOCTRINE_METADATA_CACHE', env('DOCTRINE_CACHE', 'array')),
            'namespace' => null,
        ],
        'query'        => [
            'driver'    => env('DOCTRINE_QUERY_CACHE', env('DOCTRINE_CACHE', 'array')),
            'namespace' => null,
        ],
        'result'       => [
            'driver'    => env('DOCTRINE_RESULT_CACHE', env('DOCTRINE_CACHE', 'array')),
            'namespace' => null,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Gedmo extensions
    |--------------------------------------------------------------------------
    |
    | Settings for Gedmo extensions
    | If you want to use this you will have to require
    | laravel-doctrine/extensions in your composer.json
    |
    */
    'gedmo'                      => [
        'all_mappings' => false
    ],
    /*
     |--------------------------------------------------------------------------
     | Validation
     |--------------------------------------------------------------------------
     |
     |  Enables the Doctrine Presence Verifier for Validation
     |
     */
    'doctrine_presence_verifier' => true,

    /*
     |--------------------------------------------------------------------------
     | Notifications
     |--------------------------------------------------------------------------
     |
     |  Doctrine notifications channel
     |
     */
    'notifications'              => [
        'channel' => 'database'
    ]
];
