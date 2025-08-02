<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AnalyticsController;
use Cloudinary\Asset\Analytics;

Route::middleware(['auth:admin-api', 'agent'])->group(function () {
    Route::get('/post/analytics', [AnalyticsController::class, 'postAnalytics']);
    Route::get('/alumni-user/analytics', [AnalyticsController::class, 'alumniAnalytics']);
});