<?php

use App\Http\Controllers\API\ResetPasswordController;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\VerifyEmailController;
use App\Http\Controllers\API\AuthenticationController;

Route::post('register', [AuthenticationController::class, 'register'])->name('register');

Route::post('login', [AuthenticationController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('get-user', [AuthenticationController::class, 'userInfo'])->name('get-user');
    Route::post('logout', [AuthenticationController::class, 'logOut'])->name('logout');
});

Route::post('/send-otp', [VerifyEmailController::class, 'sendOtp'])
    ->name('verification.send')
    ->middleware(['throttle:6,1']);

Route::post('/verify-otp', [VerifyEmailController::class, 'verifyOtp'])
    ->name('verification.verify')
    ->middleware(['throttle:6,1']);

Route::post('/forgot-password', [ResetPasswordController::class, 'sendResetLink'])
    ->name('password.email')
    ->middleware(['throttle:6,1']);

Route::post('/forgot-password-token', [ResetPasswordController::class, 'verifyOtp'])
    ->name('password.reset')
    ->middleware(['throttle:6,1']);

Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
    ->name('password.update')
    ->middleware(['throttle:6,1']);