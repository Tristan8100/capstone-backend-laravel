<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController;

Route::middleware(['auth:admin-api', 'agent'])->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::delete('/activity-logs/clean', [ActivityLogController::class, 'clean']);
});