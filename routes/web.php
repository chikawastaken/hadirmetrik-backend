<?php

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'health' => url('/api/v1/health'),
    ]);
});

// Preview registration email (for testing)
Route::get('/email/preview/{id}/{token}', function ($id, $token) {
    $user = User::find($id);
    if (! $user) {
        abort(404);
    }

    return view('emails.registration', [
        'name' => $user->name,
        'email' => $user->email,
        'token' => $token,
        'logoUrl' => asset('images/logo.png'),
    ]);
});
