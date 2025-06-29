<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\PostCommentController;
Route::middleware('auth:sanctum')->group(function () {
    // Posts
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // Post Comments
    Route::post('/posts/{post}/comments', [PostCommentController::class, 'store']);
    Route::get('/posts/{post}/comments', [PostCommentController::class, 'index']);
});

Route::middleware('auth:admin-api')->put('/posts/{id}/status', [PostController::class, 'updateStatus']);
