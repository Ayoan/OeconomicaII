<?php
// ===== routes/web.php =====

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
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
    // ホーム画面（入力画面にリダイレクト）
    Route::get('/home', function () {
        return redirect()->route('household.input');
    })->name('home');
    
    // 家計簿機能
    Route::prefix('household')->name('household.')->group(function () {
        // 入力画面
        Route::get('/input', [HouseholdController::class, 'input'])->name('input');
        Route::post('/input', [HouseholdController::class, 'store'])->name('store');
        // Route::get('/categories', [HouseholdController::class, 'getCategories'])->name('household.categories');
   
        // 入力画面データ編集
        Route::get('/edit/{id}', [HouseholdController::class, 'edit'])->name('household.edit');
        Route::put('/update/{id}', [HouseholdController::class, 'update'])->name('household.update');
        Route::delete('/delete/{id}', [HouseholdController::class, 'destroy'])->name('household.destroy');

        // CSV機能ルート（以下を追加）
        Route::get('/export-csv', [HouseholdController::class, 'exportCsv'])->name('export');
        Route::get('/import', [HouseholdController::class, 'importForm'])->name('import');
        Route::post('/import', [HouseholdController::class, 'importCsv'])->name('import.store');
        
        // 月データ
        Route::get('/monthly', [HouseholdController::class, 'monthly'])->name('monthly');
        
        // 年データ
        Route::get('/yearly', [HouseholdController::class, 'yearly'])->name('yearly');
        
        // 設定
        Route::get('/settings', [HouseholdController::class, 'settings'])->name('settings');
    });
});
?>