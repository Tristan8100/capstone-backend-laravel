<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AnalyticsController;


Route::middleware(['auth:admin-api', 'agent'])->group(function () {
    Route::get('/post/analytics', [AnalyticsController::class, 'postAnalytics']);
    Route::get('/alumni-user/analytics', [AnalyticsController::class, 'alumniAnalytics']);
    Route::get('/alumni-account/analytics', [AnalyticsController::class, 'userAnalytics']);
    Route::get('/institute/analytics', [AnalyticsController::class, 'instituteAnalytics']);
    Route::get('/survey/analytics', [AnalyticsController::class, 'surveyAnalytics']);
});