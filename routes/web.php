<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Curator\LandmarkController;
use App\Http\Controllers\Curator\TriviaController;
use App\Http\Controllers\Curator\DashboardController;
use App\Http\Controllers\Curator\TipReviewController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\Admin\ReportController;

Route::view('/', 'home')->name('home');
Route::view('/about', 'about')->name('about');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/curators/login', [LoginController::class, 'showLoginForm'])->name('curators.login');
Route::post('/curators/login', [LoginController::class, 'login'])->name('curators.login.submit');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::prefix('curators')->middleware('curator.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('curators.dashboard');

    Route::get('/map', [LandmarkController::class, 'map'])->name('curators.map');
    Route::resource('landmarks', LandmarkController::class);

    
    Route::get('/qr',                 [QrController::class, 'index'])->name('curators.qr');
    Route::post('/qr',                [QrController::class, 'store'])->name('curators.qr.store');
    Route::delete('/qr/{id}',         [QrController::class, 'destroy'])->name('curators.qr.destroy');
    Route::get('/qr/{id}/download',   [QrController::class, 'download'])->name('curators.qr.download');
    Route::get('/qr/{id}/view',       [QrController::class, 'view'])->name('curators.qr.view');
    Route::get('/qr/by-landmark/{landmarkId}', [QrController::class, 'downloadByLandmark'])->name('curators.qr.byLandmark');

    
    Route::get('/trivia', [TriviaController::class, 'all'])->name('curators.trivia.all');

    Route::post('/trivia', [TriviaController::class, 'store'])->name('curators.trivia.store');
    
    Route::put('/trivia/{triviaId}', [TriviaController::class, 'update'])->name('curators.trivia.update');
    Route::delete('/trivia/{triviaId}', [TriviaController::class, 'destroy'])->name('curators.trivia.destroy');

    Route::get('/tips', [TipReviewController::class, 'index'])->name('curators.tips.index');
    Route::get('/tips/data', [TipReviewController::class, 'fetchData'])->name('curators.tips.data');
    Route::post('/tips/{tipId}/review', [TipReviewController::class, 'review'])->name('curators.tips.review');
}); 


Route::get('/qr/resolve/{code}', [QrController::class, 'resolve'])->name('qr.resolve');


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
