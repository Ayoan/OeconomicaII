@extends('layouts.app')

@section('title', 'ログイン - 家計簿管理システム')

@section('content')
<div class="login-wrapper">
    <div class="login-container">
        <a href="{{ url('/') }}" class="close-btn" title="閉じる">×</a>
        
        <div class="logo-section">
            <!-- <h1 class="app-title">家計簿管理システム</h1> -->
            <h1 class="app-title">Oeconomica II</h1>
            <h2 class="login-title">ログイン</h2>
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

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            
            <div class="form-group">
                <input type="text"
                       class="form-input @error('username') is-invalid @enderror"
                       name="username"
                       value="{{ old('username') }}"
                       placeholder="ユーザー名"
                       required
                       autocomplete="username"
                       autofocus>
                @error('username')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="form-group">
                <input type="password" 
                       class="form-input @error('password') is-invalid @enderror" 
                       name="password" 
                       placeholder="パスワード" 
                       required 
                       autocomplete="current-password">
                @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group remember-group">
                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span class="checkmark"></span>
                    ログイン状態を保持する
                </label>
            </div>
            
            <button type="submit" class="login-btn">ログイン</button>
        </form>

        <div class="link-section">
            <a href="{{ route('password.request') }}" class="forgot-password">
                パスワードを忘れた方はこちら
            </a>
        </div>

        <div class="divider"></div>

        <div class="register-section">
            <a href="{{ route('register') }}" class="register-btn">
                <span class="register-icon">👤</span>
                新規会員登録
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .login-wrapper {
        min-height: calc(100vh - 120px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .login-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 50px 40px;
        width: 100%;
        max-width: 450px;
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

    .login-title {
        font-size: 24px;
        font-weight: 500;
        color: #333;
        margin-bottom: 30px;
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
        padding: 15px 20px;
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

    .form-input::placeholder {
        color: #999;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 5px;
        font-size: 14px;
        color: #dc3545;
    }

    .remember-group {
        margin-bottom: 30px;
    }

    .remember-checkbox {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #666;
        cursor: pointer;
        user-select: none;
    }

    .remember-checkbox input[type="checkbox"] {
        display: none;
    }

    .checkmark {
        width: 18px;
        height: 18px;
        border: 2px solid #e1e8ed;
        border-radius: 4px;
        margin-right: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .remember-checkbox input[type="checkbox"]:checked + .checkmark {
        background-color: #667eea;
        border-color: #667eea;
    }

    .remember-checkbox input[type="checkbox"]:checked + .checkmark::after {
        content: '✓';
        color: white;
        font-size: 12px;
        font-weight: bold;
    }

    .login-btn {
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
    }

    .login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .login-btn:active {
        transform: translateY(0);
    }

    .link-section {
        text-align: center;
        margin-bottom: 20px;
    }

    .forgot-password {
        color: #667eea;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .forgot-password:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .divider {
        width: 100%;
        height: 1px;
        background: linear-gradient(to right, transparent, #ddd, transparent);
        margin: 30px 0;
    }

    .register-section {
        text-align: center;
    }

    .register-btn {
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

    .register-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        text-decoration: none;
    }

    .register-icon {
        font-size: 18px;
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
        .login-container {
            padding: 40px 30px;
            margin: 10px;
        }

        .app-title {
            font-size: 24px;
        }

        .login-title {
            font-size: 20px;
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
    document.getElementById('loginForm').addEventListener('submit', function() {
        const submitBtn = this.querySelector('.login-btn');
        submitBtn.textContent = 'ログイン中...';
        submitBtn.disabled = true;
    });
</script>
@endpush