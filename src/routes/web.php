<?php
// ===== routes/web.php =====

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HouseholdController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ゲストユーザー向けのルート（認証していないユーザー）
Route::middleware(['guest'])->group(function () {
    // トップページ
    Route::get('/', function () {
        return redirect()->route('login');
    });

    // ログイン関連
    Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UserController::class, 'login']);

    // パスワードリセット
    Route::get('/password/reset', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');

    // 新規登録
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// API ルート（認証不要）
Route::post('/api/check-email', [RegisterController::class, 'checkEmail'])->name('api.check-email');

// ログアウト（認証済みユーザーのみ）
Route::post('/logout', [UserController::class, 'logout'])->name('logout')->middleware('auth');

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
?>