<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login brute-force protection (per IP, per channel)
    |--------------------------------------------------------------------------
    |
    | After max_attempts failed logins from the same IP on the same channel
    | (api, web, or admin), further attempts are blocked until decay_minutes pass.
    |
    */

    'max_attempts' => max(1, (int) env('LOGIN_MAX_ATTEMPTS', 5)),

    'decay_minutes' => max(1, (int) env('LOGIN_DECAY_MINUTES', 15)),

];
