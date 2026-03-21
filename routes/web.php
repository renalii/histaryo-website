<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Curator\LandmarkController;
use App\Http\Controllers\Curator\TriviaController;
use App\Http\Controllers\Curator\DashboardController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Auth;

Route::view('/', 'home')->name('home');
Route::view('/about', 'about')->name('about');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');


Route::prefix('curators')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('curators.dashboard');

    Route::get('/map', [LandmarkController::class, 'map'])->name('curators.map');
    Route::resource('landmarks', LandmarkController::class);

    
    Route::get('/qr',                 [QrController::class, 'index'])->name('curators.qr');
    Route::post('/qr',                [QrController::class, 'store'])->name('curators.qr.store');
    Route::delete('/qr/{id}',         [QrController::class, 'destroy'])->name('curators.qr.destroy');
    Route::get('/qr/{id}/download',   [QrController::class, 'download'])->name('curators.qr.download');
    Route::get('/qr/by-landmark/{landmarkId}', [QrController::class, 'downloadByLandmark'])->name('curators.qr.byLandmark');

    
    Route::get('/trivia', [TriviaController::class, 'all'])->name('curators.trivia.all');

    Route::post('/trivia', [TriviaController::class, 'store'])->name('curators.trivia.store');
    
    Route::put('/trivia/{triviaId}', [TriviaController::class, 'update'])->name('curators.trivia.update');
    Route::delete('/trivia/{triviaId}', [TriviaController::class, 'destroy'])->name('curators.trivia.destroy');
});


Route::get('/qr/resolve/{id}', [QrController::class, 'download'])->name('qr.resolve'); 


Route::get('/quiz/{landmarkId}', [TriviaController::class, 'play'])->name('quiz.play');
Route::get('/api/quiz', [TriviaController::class, 'getQuiz'])->name('quiz.fetch');


Route::get('/api/quiz-key', [TriviaController::class, 'getQuizKey'])->name('quiz.key');


Route::prefix('admin')->middleware(['web'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/curators', [AdminController::class, 'curators'])->name('admin.curators');
    Route::get('/landmarks', [AdminController::class, 'landmarks'])->name('admin.landmarks');
    Route::get('/logs', [AdminController::class, 'logs'])->name('admin.logs');
    Route::delete('/logs/clear', [AdminController::class, 'clearLogs'])->name('admin.logs.clear');

    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export/{any?}', [ReportController::class, 'export'])->name('admin.reports.export');
});
