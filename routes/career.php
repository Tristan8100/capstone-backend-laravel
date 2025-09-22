<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CareerController;

//ADMIN
Route::middleware(['auth:user-api', 'agent'])->group(function () {
    Route::post('/career', [CareerController::class, 'create']);
    Route::put('/career/{id}', [CareerController::class, 'update']);
    Route::delete('/career/{id}', [CareerController::class, 'delete']);
    Route::get('/career', [CareerController::class, 'index']);
    Route::get('/career-paginated', [CareerController::class, 'indexPaginated']);
    
    Route::get('/career/{id}', [CareerController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    Route::get('/career-paginated/{id}', [CareerController::class, 'indexPaginatedbyId']);
});