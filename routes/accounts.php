<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () { // might change later
    // Routes for fetching filter options
    Route::get('/institutes', [AccountController::class, 'institutes']);
    Route::get('/courses', [AccountController::class, 'courses']);

    // Alumni routes
    Route::get('/alumni', [AccountController::class, 'index']);
    Route::get('/alumni/{id}', [AccountController::class, 'show']);
});