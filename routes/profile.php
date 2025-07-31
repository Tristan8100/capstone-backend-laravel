<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProfileController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    // PROFILE (ALUMNI)
    Route::post('/profile-picture', [ProfileController::class, 'addPhoto']);

});
