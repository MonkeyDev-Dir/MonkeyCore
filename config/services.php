<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'apifycr' => [
        'base_url' => env('CIVIL_REGISTRY_API_URL', 'https://tse.apifycr.com/api/v2'),
        'api_key' => env('CIVIL_REGISTRY_API_TOKEN'),
        'timeout' => (int) env('APIFYCR_TIMEOUT', 8),
        'connect_timeout' => (int) env('APIFYCR_CONNECT_TIMEOUT', 3),
    ],

    'gemini' => [
        'enabled' => filter_var(env('GEMINI_ENABLED', false), FILTER_VALIDATE_BOOL),
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    ],

];
