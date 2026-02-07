<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v3 Configuration
    |--------------------------------------------------------------------------
    |
    | Configure reCAPTCHA v3 for spam protection. To enable, set both
    | RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY in your .env file.
    | Requires the google/recaptcha package: composer require google/recaptcha
    |
    */

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'score_threshold' => env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
    ],

];
