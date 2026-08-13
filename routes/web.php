<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Curator\LandmarkController;
use App\Http\Controllers\Curator\QuizController;
use App\Http\Controllers\Curator\DashboardController;
use App\Http\Controllers\Curator\TipReviewController;
use App\Http\Controllers\Curator\PasswordController;
use App\Http\Controllers\Curator\ExhibitController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\SiteManager\SiteManagerController;
use App\Http\Controllers\SiteManager\SiteManagerCuratorController;
use App\Http\Controllers\SiteManager\ExhibitCategoryController;
use App\Http\Controllers\QrController;

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
Route::get('/curators/reset-password', [PasswordController::class, 'showResetForm'])->name('curators.password-reset');
Route::post('/curators/reset-password', [PasswordController::class, 'resetPassword'])->name('curators.password-reset.update');
Route::prefix('curators')->middleware('curator.auth')->group(function () {
    Route::get('/change-password', [PasswordController::class, 'showChangeForm'])->name('curators.change-password');
    Route::post('/change-password', [PasswordController::class, 'update'])->name('curators.change-password.update');

    Route::get('/pending-assignment', [DashboardController::class, 'pendingAssignment'])->name('curators.pending-assignment');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('curators.dashboard');

    Route::get('/map', [LandmarkController::class, 'map'])->name('curators.map');
    Route::resource('landmarks', LandmarkController::class)->except(['create', 'store']);
    Route::get('/exhibit-categories', [ExhibitCategoryController::class, 'index'])->name('curators.exhibit-categories.index');
    Route::post('/exhibit-categories', [ExhibitCategoryController::class, 'store'])->name('curators.exhibit-categories.store');
    Route::put('/exhibit-categories/{id}', [ExhibitCategoryController::class, 'update'])->name('curators.exhibit-categories.update');
    Route::delete('/exhibit-categories/{id}', [ExhibitCategoryController::class, 'destroy'])->name('curators.exhibit-categories.destroy');
    Route::get('/exhibits', [ExhibitController::class, 'index'])->name('curators.exhibits.index');
    Route::post('/exhibits', [ExhibitController::class, 'store'])->name('curators.exhibits.store');
    Route::put('/exhibits/{id}', [ExhibitController::class, 'update'])->name('curators.exhibits.update');
    Route::delete('/exhibits/{id}', [ExhibitController::class, 'destroy'])->name('curators.exhibits.destroy');

    
    Route::get('/qr',                 [QrController::class, 'index'])->name('curators.qr');
    Route::post('/qr',                [QrController::class, 'store'])->name('curators.qr.store');
    Route::delete('/qr/{id}',         [QrController::class, 'destroy'])->name('curators.qr.destroy');
    Route::get('/qr/{id}/download',   [QrController::class, 'download'])->name('curators.qr.download');
    Route::get('/qr/{id}/view',       [QrController::class, 'view'])->name('curators.qr.view');
    Route::get('/qr/by-landmark/{landmarkId}', [QrController::class, 'downloadByLandmark'])->name('curators.qr.byLandmark');

    
    Route::get('/quiz', [QuizController::class, 'all'])->name('curators.quiz.all');
    Route::get('/quiz/{id}/delete', [QuizController::class, 'confirmDelete'])->name('curators.quiz.delete-confirm');
    Route::get('/quiz/{id}', [QuizController::class, 'show'])->name('curators.quiz.show');

    Route::post('/quiz', [QuizController::class, 'store'])->name('curators.quiz.store');
    
    Route::put('/quiz/{id}', [QuizController::class, 'update'])->name('curators.quiz.update');
    Route::delete('/quiz/{id}', [QuizController::class, 'destroy'])->name('curators.quiz.destroy');

    Route::get('/tips', [TipReviewController::class, 'index'])->name('curators.tips.index');
    Route::post('/tips/{tipId}/review', [TipReviewController::class, 'review'])->name('curators.tips.review');
}); 


Route::get('/resolve/{code}', [QrController::class, 'resolve'])->name('qr.resolve');
Route::get('/qr/resolve/{code}', [QrController::class, 'resolve'])->name('qr.resolve.legacy');

    
Route::prefix('admin')->middleware(['web', 'panel.admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard/role-usage', [AdminController::class, 'dashboardRoleUsage'])->name('admin.dashboard.role-usage');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/site-managers', [AdminController::class, 'users'])->name('admin.site-managers');
    Route::get('/map', [AdminController::class, 'map'])->name('admin.map');
    Route::post('/users/{uid}/approve', [AdminController::class, 'approveUser'])->name('admin.users.approve');
    Route::post('/users/{uid}/reject', [AdminController::class, 'rejectUser'])->name('admin.users.reject');
    Route::get('/landmarks', [AdminController::class, 'landmarks'])->name('admin.landmarks');
    Route::get('/landmarks/{id}', [AdminController::class, 'showLandmark'])->name('admin.landmarks.show');
    Route::post('/landmarks/{id}/approve', [AdminController::class, 'approveLandmark'])->name('admin.landmarks.approve');
    Route::post('/landmarks/{id}/reject', [AdminController::class, 'rejectLandmark'])->name('admin.landmarks.reject');
});

Route::prefix('sitemanager')->middleware(['web', 'panel.sitemanager'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('sitemanager.dashboard');
    Route::get('/curators', [AdminController::class, 'users'])->name('sitemanager.curators');
    Route::get('/exhibit-categories', [ExhibitCategoryController::class, 'index'])->name('sitemanager.exhibit-categories.index');
    Route::get('/exhibit-categories/{id}', [ExhibitCategoryController::class, 'index'])->name('sitemanager.exhibit-categories.show');
    Route::post('/exhibit-categories', [ExhibitCategoryController::class, 'store'])->name('sitemanager.exhibit-categories.store');
    Route::put('/exhibit-categories/{id}', [ExhibitCategoryController::class, 'update'])->name('sitemanager.exhibit-categories.update');
    Route::delete('/exhibit-categories/{id}', [ExhibitCategoryController::class, 'destroy'])->name('sitemanager.exhibit-categories.destroy');
    Route::get('/exhibits', [ExhibitController::class, 'index'])->name('sitemanager.exhibits.index');
    Route::get('/exhibits/{id}', [ExhibitController::class, 'index'])->name('sitemanager.exhibits.show');
    Route::post('/exhibits', [ExhibitController::class, 'store'])->name('sitemanager.exhibits.store');
    Route::put('/exhibits/{id}', [ExhibitController::class, 'update'])->name('sitemanager.exhibits.update');
    Route::delete('/exhibits/{id}', [ExhibitController::class, 'destroy'])->name('sitemanager.exhibits.destroy');
    Route::get('/map', [SiteManagerController::class, 'map'])->name('sitemanager.map');
    Route::get('/curators/create', [SiteManagerCuratorController::class, 'create'])->name('sitemanager.curators.create');
    Route::post('/curators', [SiteManagerCuratorController::class, 'store'])->name('sitemanager.curators.store');
    Route::put('/curators/{uid}', [SiteManagerCuratorController::class, 'update'])->name('sitemanager.curators.update');
    Route::delete('/curators/{uid}', [SiteManagerCuratorController::class, 'destroy'])->name('sitemanager.curators.destroy');
    Route::post('/curators/{uid}/activate', [SiteManagerCuratorController::class, 'activate'])->name('sitemanager.curators.activate');
    Route::post('/curators/{uid}/deactivate', [SiteManagerCuratorController::class, 'deactivate'])->name('sitemanager.curators.deactivate');
    Route::post('/curators/{uid}/reset-password', [SiteManagerCuratorController::class, 'sendPasswordReset'])->name('sitemanager.curators.reset-password');
    Route::post('/curators/{uid}/approve', [AdminController::class, 'approveUser'])->name('sitemanager.curators.approve');
    Route::post('/curators/{uid}/reject', [AdminController::class, 'rejectUser'])->name('sitemanager.curators.reject');
    Route::get('/landmarks', [AdminController::class, 'landmarks'])->name('sitemanager.landmarks');
    Route::get('/landmarks/create', [SiteManagerController::class, 'create'])->name('sitemanager.landmarks.create');
    Route::post('/landmarks', [SiteManagerController::class, 'store'])->name('sitemanager.landmarks.store');
    Route::put('/landmarks/{id}', [SiteManagerController::class, 'update'])->name('sitemanager.landmarks.update');
    Route::delete('/landmarks/{id}', [SiteManagerController::class, 'destroy'])->name('sitemanager.landmarks.destroy');
    Route::get('/landmarks/{id}', [AdminController::class, 'showLandmark'])->name('sitemanager.landmarks.show');
});
