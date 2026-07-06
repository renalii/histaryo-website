<?php

use App\Http\Controllers\Api\QuizResultController;
use Illuminate\Support\Facades\Route;

Route::post('/quiz-results', [QuizResultController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('api.quiz-results.store');
