<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules;

class PasswordResetController extends Controller
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
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.exists' => '指定されたメールアドレスは登録されていません。',
        ]);

        try {
            // パスワードリセットトークンの生成と保存
            $user = User::where('email', $request->email)->first();
            $token = Str::random(64);
            
            // 既存のトークンを削除して新しいトークンを保存
            $user->update([
                'reset_token' => $token,
                'reset_token_expires' => now()->addHours(1), // 1時間後に期限切れ
            ]);

            // メール送信
            Mail::to($user->email)->send(new PasswordResetMail($user, $token));

            return back()->with('status', 'パスワードリセット用のリンクをメールアドレスに送信しました。メールボックスをご確認ください。');

        } catch (\Exception $e) {
            // メール送信エラーの場合
            \Log::error('Password reset email failed: ' . $e->getMessage());
            
            return back()->withErrors([
                'email' => 'メールの送信に失敗しました。しばらく時間をおいて再度お試しください。'
            ]);
        }
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  string  $token
     * @return \Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        // トークンの有効性確認
        $user = User::where('reset_token', $token)
                   ->where('reset_token_expires', '>', now())
                   ->first();

        if (!$user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'パスワードリセットのリンクが無効または期限切れです。']);
        }

        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8', 'max:100'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワードが一致しません。',
        ]);

        try {
            // トークンとメールアドレスでユーザーを検索
            $user = User::where('email', $request->email)
                       ->where('reset_token', $request->token)
                       ->where('reset_token_expires', '>', now())
                       ->first();

            if (!$user) {
                return back()->withErrors([
                    'email' => 'パスワードリセットのリンクが無効または期限切れです。'
                ]);
            }

            // パスワードを更新
            $user->update([
                'password_hash' => Hash::make($request->password),
                'reset_token' => null,
                'reset_token_expires' => null,
            ]);

            // パスワードリセット完了後、ログイン画面にリダイレクト
            return redirect()->route('login')->with('status', 'パスワードが正常に変更されました。新しいパスワードでログインしてください。');

        } catch (\Exception $e) {
            \Log::error('Password reset failed: ' . $e->getMessage());
            
            return back()->withErrors([
                'email' => 'パスワードの変更に失敗しました。再度お試しください。'
            ]);
        }
    }
}