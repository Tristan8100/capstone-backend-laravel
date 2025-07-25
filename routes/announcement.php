<?php
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\CommentController;
use Illuminate\Support\Facades\Route;
//ADMIN
Route::middleware('auth:admin-api')->group(function () {
    Route::get('announcements', [AnnouncementController::class, 'index']);
    Route::post('announcements', [AnnouncementController::class, 'store']);
    Route::get('announcements/{id}', [AnnouncementController::class, 'show']);
    Route::put('announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('announcements/{id}', [AnnouncementController::class, 'destroy']);
});
//USER
Route::middleware('auth:sanctum')->group(function () {
    Route::post('comments', [CommentController::class, 'store']);
    Route::delete('comments/{id}', [CommentController::class, 'destroy']);
    Route::get('alumni/announcements/{id}', [AnnouncementController::class, 'show']);
    Route::get('alumni/announcements', [AnnouncementController::class, 'index']);
});