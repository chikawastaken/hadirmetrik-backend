<?php

// Webhook routes — placeholder
use Illuminate\Support\Facades\Route;

// No auth for webhooks; verify signature in controller
Route::post('/webhook/stripe', function () {
    return response()->json(['received' => true]);
});
