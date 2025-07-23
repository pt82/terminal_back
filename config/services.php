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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'facebook' => [
        'client_id' => '908050129788222',
        'client_secret' => '2e303ddef3c9c412f67ddb6cd020c793',
        'redirect' => env('APP_URL') . 'api/social-auth/callback/facebook',
    ],

    'google' => [
        'client_id' => '491000828978-g351tpftvdb9t6himlmt9meik375fln4.apps.googleusercontent.com',
        'client_secret' => 'Pxao1LVHOqOZEzx15lcznnm3',
        'redirect' => env('APP_URL') . 'api/social-auth/callback/google'
    ],

];
