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

    /*
    |--------------------------------------------------------------------------
    | LAS MVP AI Services - Google AI
    |--------------------------------------------------------------------------
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash-exp'),
    ],

    'google_cloud' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ],

    'google' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'us-central1'),
        'credentials' => storage_path('app/google-credentials.json'),
    ],

    'text_to_video' => [
        'provider' => env('VIDEO_PROVIDER', 'google_veo'),
        'mode' => env('VIDEO_MODE', 'test'), // 'test' or 'production'
        'gcs_bucket' => env('GCS_BUCKET'),
        'gcs_project_id' => env('GCS_PROJECT_ID'),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'us-central1'),
        'model' => env('VEO_MODEL', 'veo-3.1-generate-001'), // veo-3.1-generate-001, veo-3.1-fast-generate-001, veo-3.0-generate-001
    ],

    'stt' => [
        'provider' => env('STT_PROVIDER', 'google'),
    ],

    'tts' => [
        'provider' => env('TTS_PROVIDER', 'google'),
    ],

];
