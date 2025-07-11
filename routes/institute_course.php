<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\InstituteController;
use App\Http\Controllers\API\CourseController;

Route::middleware('auth:sanctum')->group(function () {
    // Institute routes
    Route::get('/institutes', [InstituteController::class, 'index']);
    Route::post('/institutes', [InstituteController::class, 'store']);
    Route::get('/institutes/{id}', [InstituteController::class, 'show']);
    Route::put('/institutes/{id}', [InstituteController::class, 'update']); //need to put _method: 'PUT' in the request body
    Route::delete('/institutes/{id}', [InstituteController::class, 'destroy']);

    // Course routes
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
});
