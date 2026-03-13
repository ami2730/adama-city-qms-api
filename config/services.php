<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file stores credentials for external services your application uses.
    | It's the conventional place for API keys, tokens, and other secrets so
    | packages and facades can easily locate them via config('services.*').
    |
    | Common examples include mail services (SES, Postmark, Resend), payment
    | gateways, notification channels (Slack), and more.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional / Example Services
    |--------------------------------------------------------------------------
    |
    | Uncomment and configure these when you integrate additional services.
    |
    */

    // 'stripe' => [
    //     'model'  => env('STRIPE_MODEL', \App\Models\User::class),
    //     'key'    => env('STRIPE_KEY'),
    //     'secret' => env('STRIPE_SECRET'),
    //     'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    // ],

    // 'paypal' => [
    //     'client_id'     => env('PAYPAL_CLIENT_ID'),
    //     'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    //     'mode'          => env('PAYPAL_MODE', 'sandbox'), // or 'live'
    // ],

];
