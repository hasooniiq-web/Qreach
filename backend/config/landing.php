<?php

return [
    /*
    | Where the public landing page sends people. Every link is optional: a button
    | only appears when its value is set in .env.
    */
    'ios_url' => env('LANDING_IOS_URL'),
    'android_url' => env('LANDING_ANDROID_URL'),
    'whatsapp' => env('LANDING_WHATSAPP'),   // digits with country code, e.g. 9647701234567

    // Shown in the "Contact us" section; the form's messages are also emailed to `email`.
    'email' => env('LANDING_EMAIL', 'info@qreach.online'),
    'phones' => array_values(array_filter(array_map('trim', explode(',', (string) env('LANDING_PHONES', '+964 775 001 0277,+964 785 001 0277'))))),
];
