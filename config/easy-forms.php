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

    /*
    |--------------------------------------------------------------------------
    | Localize Form Submissions
    |--------------------------------------------------------------------------
    |
    | On multisite installations, Statamic's form action route is not localized,
    | so validation errors always render in the default locale. When enabled,
    | the app locale is set from the site of the referring page so that error
    | messages are translated into the language of the page the form is on.
    |
    */

    'localize_submissions' => env('EASY_FORMS_LOCALIZE_SUBMISSIONS', true),

];
