<?php
use App\Http\Controllers\AlumniListController;
use Illuminate\Support\Facades\Route;
//ADMIN
Route::get('/alumni-list', [AlumniListController::class, 'index']);
Route::get('/alumni-list/{id}', [AlumniListController::class, 'show']);
Route::post('/alumni-list', [AlumniListController::class, 'store']);
Route::put('/alumni-list/{id}', [AlumniListController::class, 'update']);
Route::delete('/alumni-list/{id}', [AlumniListController::class, 'destroy']);
