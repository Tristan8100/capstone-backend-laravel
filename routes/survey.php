<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SurveyController;
use App\Http\Controllers\API\QuestionController;
use App\Http\Controllers\API\ChoiceController;
use App\Http\Controllers\API\ResponseController;

Route::middleware(['auth:sanctum', 'agent'])->group(function () {
    // Surveys
    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::get('/surveys-admin', [SurveyController::class, 'index2']); // Admin view
    Route::get('/surveys-alumni', [SurveyController::class, 'indexAlumni']);// Alumni view
    Route::get('/surveys/{id}', [SurveyController::class, 'show']);
    Route::post('/surveys', [SurveyController::class, 'store']); // modify for course (not yet implemented)
    Route::put('/surveys/{id}', [SurveyController::class, 'update']); // modify for course (not yet implemented)
    Route::delete('/surveys/{id}', [SurveyController::class, 'destroy']);

    Route::get('/surveys/check-response/{id}', [SurveyController::class, 'checkResponse']);

    Route::get('/surveys/results/{id}', [SurveyController::class, 'showResults']);
    Route::get('/surveys/results/text-responses/{id}', [SurveyController::class, 'getTextResponses']);

    // Last resort
    Route::post('/surveys/store-or-update', [SurveyController::class, 'storeOrUpdate']);
    Route::put('/surveys/store-or-update/{id}', [SurveyController::class, 'storeOrUpdate']);


    // Questions
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::put('/questions/{id}', [QuestionController::class, 'update']);
    Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

    // Choices
    Route::post('/choices', [ChoiceController::class, 'store']);
    Route::put('/choices/{id}', [ChoiceController::class, 'update']);
    Route::delete('/choices/{id}', [ChoiceController::class, 'destroy']);

    Route::delete('/choices/by-question/{id}', [ChoiceController::class, 'destroyByQuestion']);

    // Responses
    Route::get('/responses', [ResponseController::class, 'index']);
    Route::get('/responses/{id}', [ResponseController::class, 'show']);
    Route::post('/responses', [ResponseController::class, 'store']); // modify for course (not yet implemented)
    Route::delete('/responses/{id}', [ResponseController::class, 'destroy']);

    // Last resort
    Route::get('/responses/survey/{id}', [ResponseController::class, 'showBasedSurvey']);

});
