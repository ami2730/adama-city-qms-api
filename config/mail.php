<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer used to send all messages unless
    | another mailer is explicitly specified. Additional mailers can be
    | configured in the "mailers" array below.
    |
    */
    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples are provided — feel free to
    | add your own as needed.
    |
    | Supported transports: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    | "postmark", "resend", "log", "array", "failover", "roundrobin"
    |
    */
    'mailers' => [

        'smtp' => [
            'transport'     => 'smtp',
            'url'           => env('MAIL_URL'),
            'host'          => env('MAIL_HOST', '127.0.0.1'),
            'port'          => env('MAIL_PORT', 2525),
            'encryption'    => env('MAIL_ENCRYPTION', 'tls'), // Often missing in older copies — added for clarity
            'username'      => env('MAIL_USERNAME'),
            'password'      => env('MAIL_PASSWORD'),
            'timeout'       => null,
            'scheme'        => env('MAIL_SCHEME'), // 'ssl' or 'tls' — rarely used but kept for compatibility
            'local_domain'  => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel'   => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers'   => ['smtp', 'log'],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers'   => ['ses', 'postmark'],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may specify a name and address that is used globally for all emails
    | sent by your application. This can be overridden per mailable if needed.
    |
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name'    => env('MAIL_FROM_NAME', env('APP_NAME', 'Example')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If your application uses Markdown for email templates, you may customize
    | the styling of these templates here. Alternatively, publish the views
    | to customize them fully.
    |
    */
    'markdown' => [
        'theme'  => 'default',
        'paths'  => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
