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
    | Default Expiry
    |--------------------------------------------------------------------------
    |
    | Default lifetime for a newly issued or resent invitation when the caller
    | does not send an explicit value (docs/architecture.md §5.6: 3 days).
    |
    */

    'default_expiry_days' => (int) env('INVITATION_EXPIRY_DAYS', 3),

];
