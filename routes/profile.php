<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProfileController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    // PROFILE (ALUMNI)
    Route::post('/profile-picture', [ProfileController::class, 'addPhoto']);
});

Route::middleware(['auth:admin-api', 'agent'])->group(function () {
    // ADMIN PROFILE
    Route::post('/profile-picture-admin', [ProfileController::class, 'addPhotoAdmin']);
});