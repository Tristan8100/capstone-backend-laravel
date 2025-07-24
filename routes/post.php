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

    //by status
    Route::get('/posts/status/{status}', [PostController::class, 'indexStatus']); //all
    Route::get('/my-posts/status/{status}', [PostController::class, 'indexStatusMyPost']); //auth user only

    // Post Comments
    Route::post('/posts/comments', [PostCommentController::class, 'store']);
    Route::get('/posts/comments/{post}', [PostCommentController::class, 'index']);
});

Route::middleware('auth:admin-api')->put('/posts/change-status/{id}', [PostController::class, 'updateStatus']);
