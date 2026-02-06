<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Laravel's filesystem supports multiple storage locations ("disks") via
    | a unified API. This option specifies the default disk used when no
    | disk is explicitly provided (e.g., Storage::put(), $request->file()->store()).
    |
    */
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish. Each disk
    | represents a particular driver and storage location. Laravel ships with
    | support for local filesystem, Amazon S3 (and compatible services), and more.
    |
    | Supported drivers: "local", "s3", "sftp", "ftp" (via Flysystem adapters)
    |
    | Pro tip: Use env variables for credentials and bucket names in production.
    |
    */
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,           // Allows serving files via Storage::url() if needed
            'throw'  => false,
            'report' => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
            'report'     => false,
        ],

        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
            'report'                  => false,
        ],

        /*
        // Example: Scoped disk (prefixes all paths automatically – useful for user folders)
        'user-avatars' => [
            'driver' => 'scoped',
            'disk'   => 'public',
            'prefix' => 'avatars',
        ],
        */

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Laravel can automatically create symbolic links (via `php artisan storage:link`)
    | to make files in the public disk publicly accessible via the web server.
    |
    | The array key is the public symlink path; the value is the target directory.
    |
    */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
