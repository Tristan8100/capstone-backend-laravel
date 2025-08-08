<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\PostCommentController;
use App\Http\Controllers\API\PostLikeController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    // Posts
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    //by status
    Route::get('/posts/status/{status}', [PostController::class, 'indexStatus']); //all
    Route::get('/posts/status/{status}/{id}', [PostController::class, 'indexStatusUserPost']); //specific user posts
    Route::get('/my-posts/status/{status}', [PostController::class, 'indexStatusMyPost']); //auth user only
    Route::get('/posts/user/{id}', [PostController::class, 'getUserWithPosts']); //specific user, DATA on view profile

    //modified for infinite scroll
    Route::get('/posts-only/status/{status}', [PostController::class, 'indexStatusPost']);
    Route::get('/posts-only/comments/{id}', [PostController::class, 'getPostComments']); //post id
    Route::get('/posts-only/replies/{id}', [PostController::class, 'getCommentReplies']); // comment id

    // Post Comments
    Route::post('/posts/comments', [PostCommentController::class, 'store']);
    Route::get('/posts/comments/{post}', [PostCommentController::class, 'index']);
});

Route::middleware(['auth:user-api', 'agent'])->put('/posts/like/{id}', [PostLikeController::class, 'toggleLike']);

Route::middleware(['auth:admin-api', 'agent'])->put('/posts/change-status/{id}', [PostController::class, 'updateStatus']);
