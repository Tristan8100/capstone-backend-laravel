<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ConversationController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () { // might change later
    // Store a new conversation
    Route::post('/conversations', [ConversationController::class, 'store']);

    // Display all admins
    Route::get('/get-admins', [ConversationController::class, 'displayAdmins']);

    Route::get('/conversations/{id}', [ConversationController::class, 'showConversation']);
});

Route::middleware(['auth:user-api', 'agent'])->group(function () {
    // Get all conversations for the authenticated user
    Route::get('/get-conversations/user', [ConversationController::class, 'getAllConversationsforUser']);
});

Route::middleware(['auth:admin-api', 'agent'])->group(function () {
    // Get all conversations for the authenticated admin
    Route::get('/get-conversations/admin', [ConversationController::class, 'getAllConversationsforAdmin']);
});