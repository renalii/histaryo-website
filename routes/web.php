<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Curator\LandmarkController;
use App\Http\Controllers\Curator\QuizController;
use App\Http\Controllers\Curator\DashboardController;
use App\Http\Controllers\Curator\TipReviewController;
use App\Http\Controllers\Curator\PasswordController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\SiteManager\SiteManagerController;
use App\Http\Controllers\SiteManager\SiteManagerCuratorController;
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

// GET allows safe sign-out when the session/CSRF token is stale (POST still CSRF-protected).
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/curators/setup-password', [PasswordController::class, 'showSetupForm'])
    ->name('curators.setup-password')
    ->middleware('signed');
Route::post('/curators/setup-password', [PasswordController::class, 'completeSetup'])
    ->name('curators.setup-password.update')
    ->middleware('signed');

Route::prefix('curators')->middleware('curator.auth')->group(function () {
    Route::get('/change-password', [PasswordController::class, 'showChangeForm'])->name('curators.change-password');
    Route::post('/change-password', [PasswordController::class, 'update'])->name('curators.change-password.update');

    Route::get('/pending-assignment', [DashboardController::class, 'pendingAssignment'])->name('curators.pending-assignment');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('curators.dashboard');

    Route::get('/map', [LandmarkController::class, 'map'])->name('curators.map');
    Route::resource('landmarks', LandmarkController::class)->except(['create', 'store']);

    
    Route::get('/qr',                 [QrController::class, 'index'])->name('curators.qr');
    Route::post('/qr',                [QrController::class, 'store'])->name('curators.qr.store');
    Route::delete('/qr/{id}',         [QrController::class, 'destroy'])->name('curators.qr.destroy');
    Route::get('/qr/{id}/download',   [QrController::class, 'download'])->name('curators.qr.download');
    Route::get('/qr/{id}/view',       [QrController::class, 'view'])->name('curators.qr.view');
    Route::get('/qr/by-landmark/{landmarkId}', [QrController::class, 'downloadByLandmark'])->name('curators.qr.byLandmark');

    
    Route::get('/quiz', [QuizController::class, 'all'])->name('curators.quiz.all');
    Route::get('/quiz/{id}', [QuizController::class, 'show'])->name('curators.quiz.show');

    Route::post('/quiz', [QuizController::class, 'store'])->name('curators.quiz.store');
    
    Route::put('/quiz/{id}', [QuizController::class, 'update'])->name('curators.quiz.update');
    Route::delete('/quiz/{id}', [QuizController::class, 'destroy'])->name('curators.quiz.destroy');

    Route::get('/tips', [TipReviewController::class, 'index'])->name('curators.tips.index');
    Route::get('/tips/data', [TipReviewController::class, 'fetchData'])->name('curators.tips.data');
    Route::post('/tips/{tipId}/review', [TipReviewController::class, 'review'])->name('curators.tips.review');
}); 


Route::get('/qr/resolve/{code}', [QrController::class, 'resolve'])->name('qr.resolve');

    
Route::prefix('admin')->middleware(['web', 'panel.admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard/role-usage', [AdminController::class, 'dashboardRoleUsage'])->name('admin.dashboard.role-usage');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/site-managers', [AdminController::class, 'users'])->name('admin.site-managers');
    Route::post('/users/{uid}/approve', [AdminController::class, 'approveUser'])->name('admin.users.approve');
    Route::post('/users/{uid}/reject', [AdminController::class, 'rejectUser'])->name('admin.users.reject');
    Route::get('/landmarks', [AdminController::class, 'landmarks'])->name('admin.landmarks');
    Route::get('/landmarks/{id}/image', [AdminController::class, 'landmarkImage'])->name('admin.landmarks.image');
    Route::get('/landmarks/{id}', [AdminController::class, 'showLandmark'])->name('admin.landmarks.show');
    Route::post('/landmarks/{id}/approve', [AdminController::class, 'approveLandmark'])->name('admin.landmarks.approve');
    Route::post('/landmarks/{id}/reject', [AdminController::class, 'rejectLandmark'])->name('admin.landmarks.reject');
    Route::get('/logs', [AdminController::class, 'logs'])->name('admin.logs');
    Route::delete('/logs/clear', [AdminController::class, 'clearLogs'])->name('admin.logs.clear');
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/export/{any?}', [ReportController::class, 'export'])->name('admin.reports.export');
});

Route::prefix('sitemanager')->middleware(['web', 'panel.sitemanager'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('sitemanager.dashboard');
    Route::get('/curators', [AdminController::class, 'users'])->name('sitemanager.curators');
    Route::get('/curators/create', [SiteManagerCuratorController::class, 'create'])->name('sitemanager.curators.create');
    Route::post('/curators', [SiteManagerCuratorController::class, 'store'])->name('sitemanager.curators.store');
    Route::put('/curators/{uid}', [SiteManagerCuratorController::class, 'update'])->name('sitemanager.curators.update');
    Route::post('/curators/{uid}/deactivate', [SiteManagerCuratorController::class, 'deactivate'])->name('sitemanager.curators.deactivate');
    Route::post('/curators/{uid}/activate', [SiteManagerCuratorController::class, 'activate'])->name('sitemanager.curators.activate');
    Route::post('/curators/{uid}/approve', [AdminController::class, 'approveUser'])->name('sitemanager.curators.approve');
    Route::post('/curators/{uid}/reject', [AdminController::class, 'rejectUser'])->name('sitemanager.curators.reject');
    Route::get('/landmarks', [AdminController::class, 'landmarks'])->name('sitemanager.landmarks');
    Route::get('/landmarks/create', [SiteManagerController::class, 'create'])->name('sitemanager.landmarks.create');
    Route::post('/landmarks', [SiteManagerController::class, 'store'])->name('sitemanager.landmarks.store');
    Route::patch('/landmarks/{id}/visibility', [SiteManagerController::class, 'updateVisibility'])->name('sitemanager.landmarks.visibility');
    Route::get('/landmarks/{id}/image', [AdminController::class, 'landmarkImage'])->name('sitemanager.landmarks.image');
    Route::get('/landmarks/{id}', [AdminController::class, 'showLandmark'])->name('sitemanager.landmarks.show');
});
