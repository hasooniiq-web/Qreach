<?php

return [
    /*
     | Every dashboard administrator must have Google-Authenticator-style TOTP
     | enabled and pass it on each sign-in. An admin without it is sent to the
     | enrollment screen before anything else. Only turn this off for automated tests.
     */
    'require_totp' => (bool) env('ADMIN_REQUIRE_TOTP', true),
];
