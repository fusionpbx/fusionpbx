<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Existing API routes from controllers
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::get('/extensions', [\App\Http\Controllers\ExtensionController::class, 'index']);
    Route::get('/cdr', function() {
        return \App\Models\XmlCdr::latest()->limit(100)->get();
    });
});
