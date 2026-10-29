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

    'evolution' => [
        'url' => env('EVOLUTION_API_URL'),
        'key' => env('EVOLUTION_API_KEY'),
        'webhook_secret' => env('EVOLUTION_WEBHOOK_SECRET'),
    ],

    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:falecom@cardapiodigitalbh.com.br'),
    ],

    'mercadopago' => [
        'client_id' => env('MERCADOPAGO_CLIENT_ID'),
        'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
        'redirect_uri' => env('MERCADOPAGO_REDIRECT_URI', env('APP_URL').'/painel/pagamentos/mercado-pago/retorno'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        'test_mode' => env('MERCADOPAGO_TEST_MODE', true),
        'pkce_enabled' => env('MERCADOPAGO_PKCE_ENABLED', true),
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

];
