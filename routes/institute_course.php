<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\InstituteController;
use App\Http\Controllers\API\CourseController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    // Institute routes
    Route::get('/institutes-general', [InstituteController::class, 'index']);
    Route::post('/institutes-general', [InstituteController::class, 'store']);
    Route::get('/institutes-general/{id}', [InstituteController::class, 'show']);
    Route::put('/institutes-general/{id}', [InstituteController::class, 'update']); //need to put _method: 'PUT' in the request body
    Route::delete('/institutes-general/{id}', [InstituteController::class, 'destroy']);

    // Course routes
    Route::get('/courses-general', [CourseController::class, 'index']);
    Route::post('/courses-general', [CourseController::class, 'store']);
    Route::get('/courses-general/{id}', [CourseController::class, 'show']);
    Route::put('/courses-general/{id}', [CourseController::class, 'update']);
    Route::delete('/courses-general/{id}', [CourseController::class, 'destroy']);
});

Route::get('/get-courses-general', [CourseController::class, 'index2']);
Route::get('/get-institutes-general', [InstituteController::class, 'general']);
