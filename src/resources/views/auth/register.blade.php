@extends('layouts.app')

@section('title', '新規ユーザー登録 - 家計簿管理システム')

@section('content')
<div class="register-wrapper">
    <div class="register-container">
        <a href="{{ route('login') }}" class="close-btn" title="ログイン画面に戻る">×</a>

        <div class="logo-section">
            <!-- <h1 class="app-title">家計簿管理システム</h1> -->
            <h1 class="app-title">Oeconomica II</h1>
            <h2 class="register-title">新規ユーザー登録</h2>
            <p class="register-subtitle">アカウントを作成して家計管理を始めましょう</p>
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
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
            @csrf

            <div class="form-group">
                <input type="text"
                       class="form-input @error('username') is-invalid @enderror"
                       name="username"
                       value="{{ old('username') }}"
                       placeholder="ユーザー名"
                       required
                       autocomplete="username"
                       autofocus
                       maxlength="50">
                <div class="input-icon">👤</div>
                @error('username')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <div class="field-help">英数字、ひらがな、カタカナ、漢字が使用できます（最大50文字）</div>
            </div>

            <div class="form-group">
                <input type="email"
                       class="form-input @error('email') is-invalid @enderror"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="メールアドレス"
                       required
                       autocomplete="email"
                       maxlength="100">
                <div class="input-icon">✉️</div>
                <div class="email-status" id="emailStatus"></div>
                @error('email')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <div class="field-help">ログイン時に使用するメールアドレスを入力してください</div>
            </div>

            <div class="form-group">
                <input type="password"
                       class="form-input @error('password') is-invalid @enderror"
                       name="password"
                       placeholder="パスワード"
                       required
                       autocomplete="new-password"
                       id="password"
                       maxlength="100">
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

            <div class="form-group terms-group">
                <label class="terms-checkbox">
                    <input type="checkbox" name="terms_agreement" required {{ old('terms_agreement') ? 'checked' : '' }}>
                    <span class="checkmark"></span>
                    <span class="terms-text">
                        <a href="#" class="terms-link" onclick="showTerms()">利用規約</a>および
                        <a href="#" class="privacy-link" onclick="showPrivacy()">プライバシーポリシー</a>に同意する
                    </span>
                </label>
                @error('terms_agreement')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="register-btn" id="registerBtn">
                <span class="btn-text">アカウントを作成</span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner"></span>作成中...
                </span>
            </button>
        </form>

        <div class="divider"></div>

        <div class="login-section">
            <p class="login-text">すでにアカウントをお持ちの方</p>
            <a href="{{ route('login') }}" class="login-link-btn">
                <span class="login-icon">🔐</span>
                ログイン
            </a>
        </div>
    </div>
</div>

<!-- 利用規約モーダル -->
<div id="termsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>利用規約</h3>
            <span class="modal-close" onclick="closeModal('termsModal')">&times;</span>
        </div>
        <div class="modal-body">
            <h4>第1条（適用）</h4>
            <p>この利用規約は、当サービスの利用条件を定めるものです。</p>
            <!-- 利用規約の内容をここに記載 -->
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('termsModal')" class="modal-btn">閉じる</button>
        </div>
    </div>
</div>

<!-- プライバシーポリシーモーダル -->
<div id="privacyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>プライバシーポリシー</h3>
            <span class="modal-close" onclick="closeModal('privacyModal')">&times;</span>
        </div>
        <div class="modal-body">
            <h4>個人情報の取り扱いについて</h4>
            <p>当サービスでは、お客様の個人情報を適切に管理いたします。</p>
            <!-- プライバシーポリシーの内容をここに記載 -->
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('privacyModal')" class="modal-btn">閉じる</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .register-wrapper {
        min-height: calc(100vh - 120px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .register-container {
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
        max-height: 90vh;
        overflow-y: auto;
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

    .register-title {
        font-size: 24px;
        font-weight: 500;
        color: #333;
        margin-bottom: 10px;
    }

    .register-subtitle {
        font-size: 14px;
        color: #666;
        margin-bottom: 20px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 10px;
        font-size: 14px;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
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
        margin-bottom: 25px;
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 15px 50px 15px 20px;
        border: 2px solid #e1e8ed;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
    }

    .form-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        background: white;
    }

    .form-input.is-invalid {
        border-color: #dc3545;
    }

    .form-input.is-valid {
        border-color: #28a745;
    }

    .form-input::placeholder {
        color: #999;
    }

    .input-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        color: #999;
        z-index: 1;
    }

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

    .email-status {
        position: absolute;
        right: 45px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        font-weight: 500;
    }

    .email-status.checking {
        color: #ffc107;
    }

    .email-status.available {
        color: #28a745;
    }

    .email-status.unavailable {
        color: #dc3545;
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

    .field-help {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        line-height: 1.4;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 5px;
        font-size: 14px;
        color: #dc3545;
    }

    .terms-group {
        margin-bottom: 30px;
    }

    .terms-checkbox {
        display: flex;
        align-items: flex-start;
        font-size: 14px;
        color: #666;
        cursor: pointer;
        user-select: none;
        line-height: 1.5;
    }

    .terms-checkbox input[type="checkbox"] {
        display: none;
    }

    .checkmark {
        width: 18px;
        height: 18px;
        border: 2px solid #e1e8ed;
        border-radius: 4px;
        margin-right: 8px;
        margin-top: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .terms-checkbox input[type="checkbox"]:checked + .checkmark {
        background-color: #667eea;
        border-color: #667eea;
    }

    .terms-checkbox input[type="checkbox"]:checked + .checkmark::after {
        content: '✓';
        color: white;
        font-size: 12px;
        font-weight: bold;
    }

    .terms-text {
        flex: 1;
    }

    .terms-link, .privacy-link {
        color: #667eea;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .terms-link:hover, .privacy-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .register-btn {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 18px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .register-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .register-btn:active {
        transform: translateY(0);
    }

    .register-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
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

    .divider {
        width: 100%;
        height: 1px;
        background: linear-gradient(to right, transparent, #ddd, transparent);
        margin: 30px 0;
    }

    .login-section {
        text-align: center;
    }

    .login-text {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .login-link-btn {
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

    .login-link-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        text-decoration: none;
    }

    .login-icon {
        font-size: 18px;
    }

    /* モーダルスタイル */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 15px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        animation: modalSlideIn 0.3s ease-out;
    }

    .modal-header {
        padding: 20px 30px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }

    .modal-close {
        font-size: 24px;
        cursor: pointer;
        color: #999;
        transition: color 0.3s ease;
    }

    .modal-close:hover {
        color: #333;
    }

    .modal-body {
        padding: 30px;
        max-height: 400px;
        overflow-y: auto;
        line-height: 1.6;
    }

    .modal-body h4 {
        color: #333;
        margin-top: 20px;
        margin-bottom: 10px;
    }

    .modal-body h4:first-child {
        margin-top: 0;
    }

    .modal-footer {
        padding: 20px 30px;
        border-top: 1px solid #eee;
        text-align: right;
    }

    .modal-btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .modal-btn:hover {
        background: #764ba2;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        .register-container {
            padding: 30px 25px;
            margin: 10px;
            max-height: 95vh;
        }

        .app-title {
            font-size: 24px;
        }

        .register-title {
            font-size: 20px;
        }

        .form-input {
            padding: 12px 45px 12px 15px;
            font-size: 16px;
        }

        .modal-content {
            margin: 10% auto;
            width: 95%;
        }

        .modal-header,
        .modal-body,
        .modal-footer {
            padding: 20px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    let emailCheckTimeout;
    let passwordStrengthTimeout;

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

    // メールアドレス重複チェック
    document.querySelector('input[name="email"]').addEventListener('input', function() {
        clearTimeout(emailCheckTimeout);
        const email = this.value;
        const statusElement = document.getElementById('emailStatus');

        if (!email || !isValidEmail(email)) {
            statusElement.textContent = '';
            statusElement.className = 'email-status';
            return;
        }

        statusElement.textContent = '確認中...';
        statusElement.className = 'email-status checking';

        emailCheckTimeout = setTimeout(() => {
            checkEmailAvailability(email);
        }, 500);
    });

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

    // フォーム送信時のローディング状態
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('registerBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');

        // 基本バリデーション
        if (!validateForm()) {
            e.preventDefault();
            return;
        }

        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';
        submitBtn.disabled = true;
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

    // メールアドレス形式チェック
    function isValidEmail(email) {
        // const emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // メールアドレス重複チェック（AJAX）
    async function checkEmailAvailability(email) {
        try {
            const response = await fetch('/api/check-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ email: email })
            });

            const data = await response.json();
            const statusElement = document.getElementById('emailStatus');

            if (data.available) {
                statusElement.textContent = '✓ 利用可能';
                statusElement.className = 'email-status available';
            } else {
                statusElement.textContent = '✗ 使用済み';
                statusElement.className = 'email-status unavailable';
            }
        } catch (error) {
            console.error('Email check failed:', error);
            document.getElementById('emailStatus').textContent = '';
        }
    }

    // パスワード強度更新
    function updatePasswordStrength(password) {
        const strengthElement = document.getElementById('passwordStrength');
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

        // 強度に応じた色変更
        strengthElement.style.display = 'block';
    }

    // パスワード強度計算
    function calculatePasswordStrength(password) {
        let score = 0;

        // 長さチェック
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;

        // 文字種チェック
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        if (score <= 2) return { level: 'weak', text: '弱い' };
        if (score <= 3) return { level: 'fair', text: '普通' };
        if (score <= 4) return { level: 'good', text: '良い' };
        return { level: 'strong', text: '強い' };
    }

    // フォームバリデーション
    function validateForm() {
        const username = document.querySelector('input[name="username"]').value;
        const email = document.querySelector('input[name="email"]').value;
        const password = document.querySelector('input[name="password"]').value;
        const passwordConfirmation = document.querySelector('input[name="password_confirmation"]').value;
        const termsAgreement = document.querySelector('input[name="terms_agreement"]').checked;

        let isValid = true;

        // ユーザー名チェック
        if (!username.trim()) {
            showFieldError('username', 'ユーザー名を入力してください');
            isValid = false;
        } else if (username.length > 50) {
            showFieldError('username', 'ユーザー名は50文字以内で入力してください');
            isValid = false;
        }

        // メールアドレスチェック
        if (!email.trim()) {
            showFieldError('email', 'メールアドレスを入力してください');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showFieldError('email', '正しいメールアドレス形式で入力してください');
            isValid = false;
        }

        // パスワードチェック
        if (!password) {
            showFieldError('password', 'パスワードを入力してください');
            isValid = false;
        } else if (password.length < 8) {
            showFieldError('password', 'パスワードは8文字以上で入力してください');
            isValid = false;
        }

        // パスワード確認チェック
        if (password !== passwordConfirmation) {
            showFieldError('password_confirmation', 'パスワードが一致しません');
            isValid = false;
        }

        // 利用規約同意チェック
        if (!termsAgreement) {
            showFieldError('terms_agreement', '利用規約に同意してください');
            isValid = false;
        }

        return isValid;
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

    // モーダル表示
    function showTerms() {
        document.getElementById('termsModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function showPrivacy() {
        document.getElementById('privacyModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // モーダル外クリックで閉じる
    window.addEventListener('click', function(event) {
        const termsModal = document.getElementById('termsModal');
        const privacyModal = document.getElementById('privacyModal');

        if (event.target === termsModal) {
            closeModal('termsModal');
        }
        if (event.target === privacyModal) {
            closeModal('privacyModal');
        }
    });

    // Escキーでモーダルを閉じる
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal('termsModal');
            closeModal('privacyModal');
        }
    });
</script>
@endpush
