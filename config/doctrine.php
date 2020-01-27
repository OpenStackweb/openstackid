<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Mangers
    |--------------------------------------------------------------------------
    |
    | Configure your Entity Managers here. You can set a different connection
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
    'managers'                  => [
        'model' => [
            'dev'        => env('APP_DEBUG', false),
            'meta'       => env('DOCTRINE_METADATA', 'annotations'),
            'connection' => 'openstackid',
            'namespaces' => [
                'App'
            ],
            'paths'      => [
                base_path('app/Models'),
                base_path('app/libs/Auth')
            ],
            'repository' => Doctrine\ORM\EntityRepository::class,
            'proxies'    => [
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
            'events'     => [
                'listeners'   => [],
                'subscribers' => []
            ],
            'filters'    => [],
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
                'enum' => 'string'
            ]
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
    'extensions'                => [
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
    'custom_types'              => [
        'json'             => LaravelDoctrine\ORM\Types\Json::class,
        'CarbonDate'       => DoctrineExtensions\Types\CarbonDateType::class,
        'CarbonDateTime'   => DoctrineExtensions\Types\CarbonDateTimeType::class,
        'CarbonDateTimeTz' => DoctrineExtensions\Types\CarbonDateTimeTzType::class,
        'CarbonTime'       => DoctrineExtensions\Types\CarbonTimeType::class
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom datetime functions
    |--------------------------------------------------------------------------
    */
    'custom_datetime_functions' => [
        'DATEADD'  => DoctrineExtensions\Query\Mysql\DateAdd::class,
        'DATEDIFF' => DoctrineExtensions\Query\Mysql\DateDiff::class,
        'UTC_TIMESTAMP' => \App\Models\Utils\UTCTimestamp::class,
        'DATE'              =>  DoctrineExtensions\Query\Mysql\Date::class,
        'DATE_FORMAT'       => DoctrineExtensions\Query\Mysql\DateFormat::class,
        'DATESUB'           => DoctrineExtensions\Query\Mysql\DateSub::class,
        'DAY'               => DoctrineExtensions\Query\Mysql\Day::class,
        'DAYNAME'           => DoctrineExtensions\Query\Mysql\DayName::class,
        'FROM_UNIXTIME'     => DoctrineExtensions\Query\Mysql\FromUnixtime::class,
        'HOUR'              => DoctrineExtensions\Query\Mysql\Hour::class,
        'LAST_DAY'          => DoctrineExtensions\Query\Mysql\LastDay::class,
        'MINUTE'            => DoctrineExtensions\Query\Mysql\Minute::class,
        'MONTH'             => DoctrineExtensions\Query\Mysql\Month::class,
        'MONTHNAME'         => DoctrineExtensions\Query\Mysql\MonthName::class,
        'SECOND'            => DoctrineExtensions\Query\Mysql\Second::class,
        'STRTODATE'         => DoctrineExtensions\Query\Mysql\StrToDate::class,
        'TIME'              => DoctrineExtensions\Query\Mysql\Time::class,
        'TIMESTAMPADD'      => DoctrineExtensions\Query\Mysql\TimestampAdd::class,
        'TIMESTAMPDIFF'     => DoctrineExtensions\Query\Mysql\TimestampDiff::class,
        'WEEK'              => DoctrineExtensions\Query\Mysql\Week::class,
        'WEEKDAY'           => DoctrineExtensions\Query\Mysql\WeekDay::class,
        'YEAR'              => DoctrineExtensions\Query\Mysql\Year::class
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom numeric functions
    |--------------------------------------------------------------------------
    */
    'custom_numeric_functions'  => [
        'ACOS'    => DoctrineExtensions\Query\Mysql\Acos::class,
        'ASIN'    => DoctrineExtensions\Query\Mysql\Asin::class,
        'ATAN'    => DoctrineExtensions\Query\Mysql\Atan::class,
        'ATAN2'   => DoctrineExtensions\Query\Mysql\Atan2::class,
        'COS'     => DoctrineExtensions\Query\Mysql\Cos::class,
        'COT'     => DoctrineExtensions\Query\Mysql\Cot::class,
        'DEGREES' => DoctrineExtensions\Query\Mysql\Degrees::class,
        'RADIANS' => DoctrineExtensions\Query\Mysql\Radians::class,
        'SIN'     => DoctrineExtensions\Query\Mysql\Sin::class,
        'TAN'     => DoctrineExtensions\Query\Mysql\Tan::class,
        'BINARY'            => DoctrineExtensions\Query\Mysql\Binary::class,
        'CEIL'              => DoctrineExtensions\Query\Mysql\Ceil::class,
        'COUNTIF'           => DoctrineExtensions\Query\Mysql\CountIf::class,
        'CRC32'             => DoctrineExtensions\Query\Mysql\Crc32::class,
        'FLOOR'             => DoctrineExtensions\Query\Mysql\Floor::class,
        'IFELSE'            => DoctrineExtensions\Query\Mysql\IfElse::class,
        'IFNULL'            => DoctrineExtensions\Query\Mysql\IfNull::class,
        'MATCH_AGAINST'     => DoctrineExtensions\Query\Mysql\MatchAgainst::class,
        'NULLIF'            => DoctrineExtensions\Query\Mysql\NullIf::class,
        'PI'                => DoctrineExtensions\Query\Mysql\Pi::class,
        'POWER'             => DoctrineExtensions\Query\Mysql\Power::class,
        'QUARTER'           => DoctrineExtensions\Query\Mysql\Quarter::class,
        'RAND'              => DoctrineExtensions\Query\Mysql\Rand::class,
        'ROUND'             => DoctrineExtensions\Query\Mysql\Round::class,
        'STD'               => DoctrineExtensions\Query\Mysql\Std::class,
        'UUID_SHORT'        => DoctrineExtensions\Query\Mysql\UuidShort::class
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom string functions
    |--------------------------------------------------------------------------
    */
    'custom_string_functions'   => [
        'CHAR_LENGTH'      => DoctrineExtensions\Query\Mysql\CharLength::class,
        'CONCAT_WS'        => DoctrineExtensions\Query\Mysql\ConcatWs::class,
        'FIELD'            => DoctrineExtensions\Query\Mysql\Field::class,
        'FIND_IN_SET'      => DoctrineExtensions\Query\Mysql\FindInSet::class,
        'REPLACE'          => DoctrineExtensions\Query\Mysql\Replace::class,
        'SOUNDEX'          => DoctrineExtensions\Query\Mysql\Soundex::class,
        'STR_TO_DATE'      => DoctrineExtensions\Query\Mysql\StrToDate::class,
        'ASCII'            => DoctrineExtensions\Query\Mysql\Ascii::class,
        'GROUP_CONCAT'     => DoctrineExtensions\Query\Mysql\GroupConcat::class,
        'MD5'              => DoctrineExtensions\Query\Mysql\Md5::class,
        'REGEXP'           => DoctrineExtensions\Query\Mysql\Regexp::class,
        'SHA1'             => DoctrineExtensions\Query\Mysql\Sha1::class,
        'SHA2'             => DoctrineExtensions\Query\Mysql\Sha2::class,
        'SUBSTRING_INDEX'  => DoctrineExtensions\Query\Mysql\SubstringIndex::class
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
    'logger' => env('DOCTRINE_LOGGER', 'LaravelDoctrine\ORM\Loggers\FileLogger'),
    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configure meta-data, query and result caching here.
    | Optionally you can enable second level caching.
    |
    | Available: acp|array|file|memcached|redis|void
    |
    */
    'cache'                     => [
        'default'                => env('DOCTRINE_CACHE', 'redis'),
        'namespace'              => null,
        'second_level'           => [
            'enabled'                => true,
            'region_lifetime'        => 3600,
            'region_lock_lifetime'   => 60,
            'regions'                => [

            ],
            'log_enabled'  => true,
            'file_lock_region_directory' => '/tmp'
        ]

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
    'gedmo'                     => [
        'all_mappings' => false
    ]
];
