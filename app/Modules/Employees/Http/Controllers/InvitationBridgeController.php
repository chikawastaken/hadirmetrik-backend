<?php

namespace App\Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Public bridge between the invitation email (HTTP, clickable everywhere) and
 * the app's custom scheme. Dev-only replacement for a universal link; see
 * docs/architecture.md §12.6.
 */
final class InvitationBridgeController extends Controller
{
    public function __invoke(string $token): Response
    {
        return response()
            ->view('invitations.bridge', ['token' => $token])
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
