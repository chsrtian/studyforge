<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'),
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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'text_timeout' => (int) env('OPENAI_TEXT_TIMEOUT', 30),
        'json_timeout' => (int) env('OPENAI_JSON_TIMEOUT', 40),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 10),
        'retries' => (int) env('OPENAI_RETRIES', 0),
        'retry_delay_ms' => (int) env('OPENAI_RETRY_DELAY_MS', 500),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'text_timeout' => (int) env('GEMINI_TEXT_TIMEOUT', 30),
        'json_timeout' => (int) env('GEMINI_JSON_TIMEOUT', 40),
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 10),
        'retries' => (int) env('GEMINI_RETRIES', 0),
        'retry_delay_ms' => (int) env('GEMINI_RETRY_DELAY_MS', 500),
    ],
];
