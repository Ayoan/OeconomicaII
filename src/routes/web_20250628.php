<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\RegisterController;

// 認証関連ルート
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

// パスワードリセット
Route::get('/password/reset', [UserController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [UserController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [UserController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [UserController::class, 'reset'])->name('password.update');

// 新規登録
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// メールアドレス重複チェックAPI（AJAX用）
Route::post('/api/check-email', [RegisterController::class, 'checkEmail'])->name('api.check-email');

// 認証が必要なルート
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // 家計簿機能
    Route::prefix('household')->name('household.')->group(function () {
        Route::get('/input', [HouseholdController::class, 'input'])->name('input');
        Route::get('/monthly', [HouseholdController::class, 'monthly'])->name('monthly');
        Route::get('/yearly', [HouseholdController::class, 'yearly'])->name('yearly');
        Route::get('/settings', [HouseholdController::class, 'settings'])->name('settings');
    });
});