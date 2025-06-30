@extends('layouts.app')

@section('title', 'パスワードリセット - 家計簿管理システム')

@section('content')
<div class="password-reset-wrapper">
    <div class="password-reset-container">
        <a href="{{ route('login') }}" class="close-btn" title="ログイン画面に戻る">×</a>

        <div class="logo-section">
            <h1 class="app-title">家計簿管理システム</h1>
            <h2 class="reset-title">パスワードリセット</h2>
            <p class="reset-subtitle">登録されたメールアドレスにパスワードリセット用のリンクをお送りします</p>
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

        @if (session('status'))
            <div class="alert alert-success">
                <div class="success-icon">✉️</div>
                <div class="success-content">
                    <strong>メールを送信しました</strong>
                    <p>{{ session('status') }}</p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="passwordResetForm">
            @csrf

            <div class="form-group">
                <input type="email"
                       class="form-input @error('email') is-invalid @enderror"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="メールアドレス"
                       required
                       autocomplete="email"
                       autofocus>
                <div class="input-icon">✉️</div>
                @error('email')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <div class="field-help">アカウントに登録されているメールアドレスを入力してください</div>
            </div>

            <button type="submit" class="reset-btn" id="resetBtn">
                <span class="btn-text">
                    <span class="btn-icon">📤</span>
                    リセットリンクを送信
                </span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner"></span>送信中...
                </span>
            </button>
        </form>

        <div class="info-section">
            <div class="info-card">
                <div class="info-icon">💡</div>
                <div class="info-content">
                    <h4>リセット手順</h4>
                    <ol class="reset-steps">
                        <li>上記にメールアドレスを入力</li>
                        <li>「リセットリンクを送信」をクリック</li>
                        <li>メールボックスを確認</li>
                        <li>メール内のリンクをクリック</li>
                        <li>新しいパスワードを設定</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="back-section">
            <p class="back-text">ログイン画面に戻る</p>
            <a href="{{ route('login') }}" class="back-link-btn">
                <span class="back-icon">🔙</span>
                ログイン画面へ
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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

    .close-btn {
        position: absolute;
        top: 20px;
        right: 25px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
        padding: 5px;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .close-btn:hover {
        background: rgba(0, 0, 0, 0.1);
        color: #333;
        text-decoration: none;
    }

    .logo-section {
        text-align: center;
        margin-bottom: 40px;
    }

    .app-title {
        font-size: 28px;
        font-weight: 300;
        color: #333;
        margin-bottom: 10px;
        letter-spacing: 1px;
    }

    .reset-title {
        font-size: 24px;
        font-weight: 500;
        color: #333;
        margin-bottom: 10px;
    }

    .reset-subtitle {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .alert {
        padding: 20px;
        margin-bottom: 30px;
        border-radius: 12px;
        font-size: 14px;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border: 1px solid #c3e6cb;
        color: #155724;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .success-icon {
        font-size: 24px;
        flex-shrink: 0;
    }

    .success-content strong {
        display: block;
        margin-bottom: 5px;
        font-size: 16px;
    }

    .success-content p {
        margin: 0;
        opacity: 0.9;
    }

    .error-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .error-list li {
        margin-bottom: 5px;
    }

    .form-group {
        margin-bottom: 30px;
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 18px 50px 18px 20px;
        border: 2px solid #e1e8ed;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
    }

    .form-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        background: white;
        transform: translateY(-2px);
    }

    .form-input.is-invalid {
        border-color: #dc3545;
    }

    .form-input::placeholder {
        color: #999;
    }

    .input-icon {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #999;
    }

    .field-help {
        font-size: 12px;
        color: #666;
        margin-top: 8px;
        line-height: 1.4;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 8px;
        font-size: 14px;
        color: #dc3545;
    }

    .reset-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 40px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .reset-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .reset-btn:active {
        transform: translateY(0);
    }

    .reset-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .btn-text {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-icon {
        font-size: 18px;
    }

    .btn-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .info-section {
        margin-bottom: 30px;
    }

    .info-card {
        background: rgba(102, 126, 234, 0.05);
        border: 1px solid rgba(102, 126, 234, 0.1);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        gap: 15px;
    }

    .info-icon {
        font-size: 24px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .info-content h4 {
        margin: 0 0 12px 0;
        font-size: 16px;
        color: #333;
        font-weight: 500;
    }

    .reset-steps {
        margin: 0;
        padding-left: 20px;
        color: #666;
        font-size: 14px;
        line-height: 1.6;
    }

    .reset-steps li {
        margin-bottom: 4px;
    }

    .divider {
        width: 100%;
        height: 1px;
        background: linear-gradient(to right, transparent, #ddd, transparent);
        margin: 30px 0;
    }

    .back-section {
        text-align: center;
    }

    .back-text {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .back-link-btn {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        padding: 12px 30px;
        border-radius: 10px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .back-link-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        text-decoration: none;
    }

    .back-icon {
        font-size: 16px;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 480px) {
        .password-reset-container {
            padding: 30px 25px;
            margin: 10px;
        }

        .app-title {
            font-size: 24px;
        }

        .reset-title {
            font-size: 20px;
        }

        .form-input {
            padding: 15px 45px 15px 15px;
        }

        .info-card {
            flex-direction: column;
            gap: 10px;
        }

        .info-icon {
            align-self: flex-start;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // フォームのインタラクション強化
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });

        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // フォーム送信時のローディング状態
    document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('resetBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');

        const email = document.querySelector('input[name="email"]').value;

        // 基本バリデーション
        if (!email.trim()) {
            e.preventDefault();
            showFieldError('email', 'メールアドレスを入力してください');
            return;
        }

        if (!isValidEmail(email)) {
            e.preventDefault();
            showFieldError('email', '正しいメールアドレス形式で入力してください');
            return;
        }

        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';
        submitBtn.disabled = true;
    });

    // メールアドレス形式チェック
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // フィールドエラー表示
    function showFieldError(fieldName, message) {
        const field = document.querySelector(`input[name="${fieldName}"]`);
        field.classList.add('is-invalid');

        let feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('span');
            feedback.className = 'invalid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;

        // エラーを3秒後に自動で消去
        setTimeout(() => {
            field.classList.remove('is-invalid');
            if (feedback) feedback.remove();
        }, 3000);
    }
</script>
@endpush
