<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue system supports multiple backends via a unified API.
    | This option defines the default connection name used when dispatching
    | jobs unless another is explicitly specified.
    |
    */
    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each backend
    | queue driver used by your application. Laravel provides support for
    | many popular queue drivers out of the box.
    |
    | Supported drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |                    "null", "failover"
    |
    | Note: "deferred" and "background" are legacy/internal and rarely used.
    |
    */
    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'       => 'database',
            'connection'   => env('DB_QUEUE_CONNECTION'),
            'table'        => env('DB_QUEUE_TABLE', 'jobs'),
            'queue'        => env('DB_QUEUE', 'default'),
            'retry_after'  => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver'      => 'beanstalkd',
            'host'        => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'port'        => env('BEANSTALKD_QUEUE_PORT', 11300), // Added common default
            'queue'       => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for'   => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.'),
            'queue'  => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver'       => 'redis',
            'connection'   => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for'    => null,
            'after_commit' => false,
        ],

        // Useful for high-availability setups (try connections in order)
        'failover' => [
            'driver'      => 'failover',
            'connections' => ['redis', 'database', 'sync'],
            'retry_after' => 60,
        ],

        // Discards all jobs (useful for testing or disabling queues)
        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | When using job batching, these values determine the database and table
    | used to store batch metadata. You may change them to any connection/table
    | defined in your application.
    |
    */
    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'), // Often better default than sqlite in prod
        'table'    => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure how failed jobs are logged and stored.
    | Laravel supports several drivers for persisting failed jobs.
    |
    | Supported: "database-uuids", "dynamodb", "file", "null"
    |
    */
    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],

];
