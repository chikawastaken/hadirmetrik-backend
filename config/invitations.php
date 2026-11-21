<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invitation Link
    |--------------------------------------------------------------------------
    |
    | Base URL used when building the face-enrollment invitation link. The
    | plain token is appended as a path segment. In production this should
    | point at the mobile deep-link bridge, never at an API endpoint that
    | logs query strings.
    |
    */

    'url' => env('INVITATION_URL')
        ?: rtrim((string) env('APP_URL', 'http://localhost'), '/').'/invitations',

    /*
    |--------------------------------------------------------------------------
    | Explicit Override
    |--------------------------------------------------------------------------
    |
    | Set INVITATION_URL to pin the link (for example a public HTTPS domain).
    | When it is empty, the dispatcher derives the host from the request that
    | created the invitation, falling back to this machine's LAN IPv4 for
    | loopback requests (adb reverse, artisan commands).
    |
    */

    'explicit_url' => env('INVITATION_URL') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Default Expiry
    |--------------------------------------------------------------------------
    |
    | Default lifetime for a newly issued or resent invitation when the caller
    | does not send an explicit value (docs/architecture.md §5.6: 3 days).
    |
    */

    'default_expiry_days' => (int) env('INVITATION_EXPIRY_DAYS', 3),

];
