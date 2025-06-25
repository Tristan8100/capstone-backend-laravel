<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('get-user', [AuthenticationController::class, 'userInfo'])->name('get-user');
    Route::post('logout', [AuthenticationController::class, 'logOut'])->name('logout');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/alumnilist.php';