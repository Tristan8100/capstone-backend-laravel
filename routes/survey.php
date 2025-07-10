<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SurveyController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\ChoiceController;
use App\Http\Controllers\API\ResponseController;

Route::middleware('auth:sanctum')->group(function () {
    // Surveys
    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::get('/surveys/{id}', [SurveyController::class, 'show']);
    Route::post('/surveys', [SurveyController::class, 'store']);
    Route::put('/surveys/{id}', [SurveyController::class, 'update']);
    Route::delete('/surveys/{id}', [SurveyController::class, 'destroy']);

    // Questions
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::put('/questions/{id}', [QuestionController::class, 'update']);
    Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

    // Choices
    Route::post('/choices', [ChoiceController::class, 'store']);
    Route::put('/choices/{id}', [ChoiceController::class, 'update']);
    Route::delete('/choices/{id}', [ChoiceController::class, 'destroy']);

    // Responses
    Route::get('/responses', [ResponseController::class, 'index']);
    Route::get('/responses/{id}', [ResponseController::class, 'show']);
    Route::post('/responses', [ResponseController::class, 'store']);
    Route::delete('/responses/{id}', [ResponseController::class, 'destroy']);

});
