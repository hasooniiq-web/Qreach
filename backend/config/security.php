<?php

return [
    /*
    | Escalating sign-in lockout. After `threshold` wrong secrets in a row for one
    | account the account is locked for the first duration; each further lockout uses
    | the next one (1 h, 10 h, 24 h, 3 days, then 7 days for good).
    | A correct secret clears the history. Quiet for `streak_window` seconds: the
    | running count of wrong tries starts over. Quiet for `decay_days`: the level
    | goes back to the first one.
    */
    'lockout' => [
        'threshold' => (int) env('LOCKOUT_THRESHOLD', 3),
        'durations' => [3600, 36000, 86400, 259200, 604800],
        'streak_window' => 86400,
        'decay_days' => 30,
    ],

    /*
    | One-time-code sending caps, counted from the otp_codes table (so they hold across
    | restarts and cannot be dodged by writing the phone number differently). A code
    | costs an SMS, so these also protect the bill.
    */
    'otp' => [
        'per_phone_hour' => (int) env('OTP_PER_PHONE_HOUR', 5),
        'per_phone_day' => (int) env('OTP_PER_PHONE_DAY', 10),
        'per_ip_hour' => (int) env('OTP_PER_IP_HOUR', 15),
        'per_ip_day' => (int) env('OTP_PER_IP_DAY', 40),
        'global_day' => (int) env('OTP_GLOBAL_DAY', 3000),
        // Each resend within the hour waits twice as long as the one before, up to this many seconds.
        'max_cooldown' => 900,
    ],

    /*
    | Flood ceilings, requests per minute per network address (see ThrottleByAddress).
    | High on purpose: a mobile carrier puts many customers behind one address and an
    | office puts several admins behind one. Stricter limits sit on the sensitive routes.
    */
    'web_per_minute' => (int) env('WEB_RATE_PER_MINUTE', 600),
    'api_per_minute' => (int) env('API_RATE_PER_MINUTE', 600),
];
