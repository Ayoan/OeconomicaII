<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // ミドルウェアの設定はroutes/web.phpで行う
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // バリデーション
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'password.required' => 'パスワードを入力してください。',
        ]);

        // レート制限チェック
        $this->ensureIsNotRateLimited($request);

        // ユーザーが存在するかチェック
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            // ユーザーが存在しない場合のログ
            \Log::warning('Login attempt with non-existent email', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // レート制限カウンターを増加
            $this->hitRateLimit($request);
            
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が正しくありません。',
            ]);
        }

        // パスワードを確認（password_hash フィールドを使用）
        if (!Hash::check($request->password, $user->password_hash)) {
            // パスワード不正の場合のログ
            \Log::warning('Login attempt with incorrect password', [
                'user_id' => $user->id,
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // レート制限カウンターを増加
            $this->hitRateLimit($request);
            
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が正しくありません。',
            ]);
        }

        // ログイン成功 - レート制限カウンターをクリア
        $this->clearRateLimit($request);

        // ユーザーをログイン状態にする
        Auth::login($user, $request->filled('remember'));

        // セッション再生成（セキュリティ対策）
        $request->session()->regenerate();

        // ログイン成功ログ
        \Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'remember' => $request->filled('remember')
        ]);

        // ログイン後のリダイレクト先を決定
        $intendedUrl = $request->session()->get('url.intended');
        
        if ($intendedUrl) {
            return redirect($intendedUrl);
        }

        // 要件に従い、ログイン後は「入力」画面を表示
        return redirect()->route('household.input')->with('status', 'ログインしました。');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        // ログアウトログ
        if ($user) {
            \Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'ログアウトしました。');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request)
    {
        $key = $this->getRateLimitKey($request);
        $maxAttempts = 5; // 5回まで失敗可能
        $decayMinutes = 15; // 15分間制限

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            
            // レート制限に引っかかった場合のログ
            \Log::warning('Rate limit exceeded for login', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'available_in_seconds' => $seconds,
                'max_attempts' => $maxAttempts
            ]);
            
            throw ValidationException::withMessages([
                'email' => "ログイン試行回数が上限（{$maxAttempts}回）に達しました。{$minutes}分後に再試行してください。",
            ]);
        }
    }

    /**
     * Increment the login attempts for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function hitRateLimit(Request $request)
    {
        $key = $this->getRateLimitKey($request);
        $decayMinutes = 15; // 15分間

        RateLimiter::hit($key, $decayMinutes * 60);
        
        // 現在の試行回数をログに記録
        $attempts = RateLimiter::attempts($key);
        \Log::info('Login attempt recorded', [
            'ip' => $request->ip(),
            'email' => $request->input('email'),
            'current_attempts' => $attempts,
            'max_attempts' => 5
        ]);
    }

    /**
     * Clear the login rate limiter for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearRateLimit(Request $request)
    {
        $key = $this->getRateLimitKey($request);
        RateLimiter::clear($key);
        
        \Log::info('Rate limit cleared for successful login', [
            'ip' => $request->ip(),
            'email' => $request->input('email')
        ]);
    }

    /**
     * Get the rate limit key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getRateLimitKey(Request $request)
    {
        // IPアドレスベースでレート制限
        // より厳密にする場合は、メールアドレスも含めることも可能
        return 'login.' . $request->ip();
        
        // メールアドレス + IPアドレスでの制限例：
        // return 'login.' . $request->input('email') . '|' . $request->ip();
    }

    /**
     * Get the current number of login attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    public function getLoginAttempts(Request $request)
    {
        $key = $this->getRateLimitKey($request);
        return RateLimiter::attempts($key);
    }

    /**
     * Get the number of seconds until the rate limit is cleared.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    public function getRateLimitTimeRemaining(Request $request)
    {
        $key = $this->getRateLimitKey($request);
        return RateLimiter::availableIn($key);
    }
}