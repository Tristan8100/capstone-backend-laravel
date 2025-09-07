<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\JobFitAnalysisController;

//ADMIN
Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    Route::get('/jobfit/overall', [JobFitAnalysisController::class, 'overall']);
    Route::get('/jobfit/course/{courseId}', [JobFitAnalysisController::class, 'perCourse']);
    Route::get('/jobfit/institute/{instituteId}', [JobFitAnalysisController::class, 'perInstitute']);
    Route::get('/jobfit/detailed', [JobFitAnalysisController::class, 'detailed']);

    Route::get('/jobfit', [JobFitAnalysisController::class, 'index']);
});