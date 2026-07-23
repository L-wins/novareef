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
        'token' => env('POSTMARK_TOKEN'),
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

    'twilio' => [
        'sid'   => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from'  => env('TWILIO_FROM'),
    ],

    'whatsapp' => [
        'token'                 => env('WHATSAPP_TOKEN'),
        'phone_number_id'       => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id'   => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'modo_prueba'           => env('WHATSAPP_MODO_PRUEBA', true),
        // Nombre exacto de la plantilla aprobada en Meta (Business Suite →
        // Administrador de WhatsApp → Plantillas) — sin esto configurado,
        // NotificarDesignacionJob simplemente no envía WhatsApp (el correo
        // sigue siendo el canal garantizado).
        'plantilla_designacion' => env('WHATSAPP_PLANTILLA_DESIGNACION'),
    ],

];
