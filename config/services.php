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

    'payment' => [
        'bank' => env('PAYMENT_BANK', 'BCA'),
        'account_number' => env('PAYMENT_ACCOUNT_NUMBER', '1234567890'),
        'account_name' => env('PAYMENT_ACCOUNT_NAME', 'BUMDes Wisata Teduh'),
        'whatsapp' => env('PAYMENT_WHATSAPP', '081234567890'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_API_TOKEN'),
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-3.8-flash-lite-tts'),
        'tts_fallback_model' => env('GEMINI_TTS_FALLBACK_MODEL', 'gemini-2.5-flash-preview-tts'),
        'tts_voice' => env('GEMINI_TTS_VOICE', 'Despina'),
    ],

];
