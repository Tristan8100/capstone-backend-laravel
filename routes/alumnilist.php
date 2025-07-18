<?php
use App\Http\Controllers\AlumniListController;
use Illuminate\Support\Facades\Route;
//ADMIN
Route::middleware('auth:admin-api')->group(function () {
    Route::get('/alumni-list', [AlumniListController::class, 'index']);
    Route::get('/alumni-list/{id}', [AlumniListController::class, 'show']);
    Route::post('/alumni-list', [AlumniListController::class, 'store']);
    Route::put('/alumni-list/{id}', [AlumniListController::class, 'update']);
    Route::delete('/alumni-list/{id}', [AlumniListController::class, 'destroy']);

    Route::post('/alumni-list/import', [AlumniListController::class, 'import']);
});

