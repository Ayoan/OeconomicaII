@extends('layouts.app')

@section('title', 'パスワード変更 - 家計簿管理システム')

@section('content')
<div class="password-reset-wrapper">
    <div class="password-reset-container">
        <a href="{{ route('login') }}" class="close-btn" title="ログイン画面に戻る">×</a>

        <div class="logo-section">
            <h1 class="app-title">家計簿管理システム</h1>
            <h2 class="reset-title">新しいパスワードの設定</h2>
            <p class="reset-subtitle">新しいパスワードを入力してアカウントのセキュリティを回復してください</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" id="passwordUpdateForm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <input type="email"
                       class="form-input @error('email') is-invalid @enderror"
                       name="email"
                       value="{{ $email ?? old('email') }}"
                       placeholder="メールアドレス"
                       required
                       autocomplete="email"
                       readonly>
                <div class="input-icon">✉️</div>
                @error('email')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <input type="password"
                       class="form-input @error('password') is-invalid @enderror"
                       name="password"
                       placeholder="新しいパスワード"
                       required
                       autocomplete="new-password"
                       id="password">
                <div class="input-icon">🔒</div>
                <div class="password-toggle" onclick="togglePassword('password')">👁️</div>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <div class="strength-progress" id="strengthProgress"></div>
                    </div>
                    <div class="strength-text" id="strengthText">パスワード強度: 未設定</div>
                </div>
                @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <div class="field-help">8文字以上で、英数字を含むパスワードを設定してください</div>
            </div>

            <div class="form-group">
                <input type="password"
                       class="form-input @error('password_confirmation') is-invalid @enderror"
                       name="password_confirmation"
                       placeholder="パスワード確認"
                       required
                       autocomplete="new-password"
                       id="passwordConfirmation">
                <div class="input-icon">🔒</div>
                <div class="password-toggle" onclick="togglePassword('passwordConfirmation')">👁️</div>
                <div class="password-match" id="passwordMatch"></div>
                @error('password_confirmation')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="reset-btn" id="updateBtn">
                <span class="btn-text">
                    <span class="btn-icon">🔄</span>
                    パスワードを変更
                </span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner"></span>変更中...
                </span>
            </button>
        </form>

        <div class="security-info">
            <div class="security-card">
                <div class="security-icon">🛡️</div>
                <div class="security-content">
                    <h4>セキュリティのヒント</h4>
                    <ul class="security-tips">
                        <li>他のサイトでは使用していない固有のパスワードを設定</li>
                        <li>大文字・小文字・数字・記号を組み合わせる</li>
                        <li>定期的なパスワード変更を心がける</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* 基本スタイルは email.blade.php と同じ */
    .password-reset-wrapper {
        min-height: calc(100vh - 120px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .password-reset-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 50px 40px;
        width: 100%;
        max-width: 500px;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: slideInUp 0.6s ease-out;
    }

    /* パスワード強度表示のスタイル追加 */
    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        cursor: pointer;
        color: #999;
        transition: color 0.3s ease;
        z-index: 2;
    }

    .password-toggle:hover {
        color: #667eea;
    }

    .password-strength {
        margin-top: 8px;
    }

    .strength-bar {
        width: 100%;
        height: 4px;
        background: #e1e8ed;
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 5px;
    }

    .strength-progress {
        height: 100%;
        width: 0%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }

    .strength-progress.weak {
        background: #dc3545;
        width: 25%;
    }

    .strength-progress.fair {
        background: #ffc107;
        width: 50%;
    }

    .strength-progress.good {
        background: #fd7e14;
        width: 75%;
    }

    .strength-progress.strong {
        background: #28a745;
        width: 100%;
    }

    .strength-text {
        font-size: 12px;
        color: #666;
    }

    .password-match {
        position: absolute;
        right: 45px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        font-weight: 500;
    }

    .password-match.match {
        color: #28a745;
    }

    .password-match.no-match {
        color: #dc3545;
    }

    .security-info {
        margin-top: 30px;
    }

    .security-card {
        background: rgba(72, 187, 120, 0.05);
        border: 1px solid rgba(72, 187, 120, 0.1);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        gap: 15px;
    }

    .security-icon {
        font-size: 24px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .security-content h4 {
        margin: 0 0 12px 0;
        font-size: 16px;
        color: #333;
        font-weight: 500;
    }

    .security-tips {
        margin: 0;
        padding-left: 20px;
        color: #666;
        font-size: 14px;
        line-height: 1.6;
    }

    .security-tips li {
        margin-bottom: 6px;
    }

    /* 他のスタイルは email.blade.php から継承 */
</style>
@endpush

@push('scripts')
<script>
    let passwordStrengthTimeout;

    // パスワード強度チェック
    document.getElementById('password').addEventListener('input', function() {
        clearTimeout(passwordStrengthTimeout);
        const password = this.value;

        passwordStrengthTimeout = setTimeout(() => {
            updatePasswordStrength(password);
        }, 100);
    });

    // パスワード確認チェック
    document.getElementById('passwordConfirmation').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmation = this.value;
        const matchElement = document.getElementById('passwordMatch');

        if (!confirmation) {
            matchElement.textContent = '';
            matchElement.className = 'password-match';
            return;
        }

        if (password === confirmation) {
            matchElement.textContent = '✓';
            matchElement.className = 'password-match match';
        } else {
            matchElement.textContent = '✗';
            matchElement.className = 'password-match no-match';
        }
    });

    // パスワード表示切り替え
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const toggle = input.parentElement.querySelector('.password-toggle');

        if (input.type === 'password') {
            input.type = 'text';
            toggle.textContent = '🙈';
        } else {
            input.type = 'password';
            toggle.textContent = '👁️';
        }
    }

    // パスワード強度更新
    function updatePasswordStrength(password) {
        const progressElement = document.getElementById('strengthProgress');
        const textElement = document.getElementById('strengthText');

        if (!password) {
            progressElement.className = 'strength-progress';
            textElement.textContent = 'パスワード強度: 未設定';
            return;
        }

        const strength = calculatePasswordStrength(password);

        progressElement.className = `strength-progress ${strength.level}`;
        textElement.textContent = `パスワード強度: ${strength.text}`;
    }

    // パスワード強度計算
    function calculatePasswordStrength(password) {
        let score = 0;

        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        if (score <= 2) return { level: 'weak', text: '弱い' };
        if (score <= 3) return { level: 'fair', text: '普通' };
        if (score <= 4) return { level: 'good', text: '良い' };
        return { level: 'strong', text: '強い' };
    }

    // フォーム送信時の処理
    document.getElementById('passwordUpdateForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('updateBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');

        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';
        submitBtn.disabled = true;
    });
</script>
@endpush
