<?php

return [
    /*
    | Days a paid plan keeps working after its period ended. Inside this window
    | the customer can still renew without losing anything; after it the account
    | returns to Basic and the Plus-only data is hidden (never deleted).
    */
    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),

    /*
    | How many days before the end of a period the customer is reminded.
    */
    'reminder_days' => [30, 10, 1],
];
