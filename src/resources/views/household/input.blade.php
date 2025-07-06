@extends('layouts.app')

@section('title', '入力 - 家計簿管理システム')

@section('content')
<div class="input-wrapper">
    <div class="container">
        <!-- ヘッダー部分 -->
        <div class="page-header">
            <h1 class="page-title">
                <span class="title-icon">📝</span>
                家計簿入力
            </h1>
            <p class="page-subtitle">日々の収支を記録しましょう</p>
        </div>

        <!-- 成功・エラーメッセージ -->
        @if (session('success'))
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                {{ session('success') }}
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-info">
                <span class="alert-icon">ℹ️</span>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- 年月選択 -->
        <div class="date-selector-card">
            <form method="GET" action="{{ route('household.input') }}" class="date-form">
                <div class="date-controls">
                    <div class="date-group">
                        <label for="year" class="date-label">年</label>
                        <select name="year" id="year" class="date-select">
                            @for ($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 1; $y++)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>
                                    {{ $y }}年
                                </option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="date-group">
                        <label for="month" class="date-label">月</label>
                        <select name="month" id="month" class="date-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                    {{ $m }}月
                                </option>
                            @endfor
                        </select>
                    </div>
                    
                    <button type="submit" class="switch-btn">
                        <span class="btn-icon">🔄</span>
                        切替
                    </button>
                </div>
                
                <div class="current-period">
                    <span class="period-text">表示期間: {{ $currentYear }}年{{ $currentMonth }}月</span>
                </div>
            </form>
        </div>

        <!-- 入力フォーム -->
        <div class="input-form-card">
            <h2 class="card-title">
                <span class="card-icon">💰</span>
                収支登録
            </h2>
            
            <form method="POST" action="{{ route('household.store') }}" class="input-form">
                @csrf
                
                <div class="form-row">
                    <!-- 収支区分 -->
                    <div class="form-group">
                        <label class="form-label">収支区分</label>
                        <div class="radio-group">
                            <label class="radio-option income">
                                <input type="radio" name="balance" value="income" {{ old('balance') == 'income' ? 'checked' : '' }} required>
                                <span class="radio-custom"></span>
                                <span class="radio-text">収入</span>
                            </label>
                            <label class="radio-option expense">
                                <input type="radio" name="balance" value="expense" {{ old('balance') == 'expense' ? 'checked' : '' }} required>
                                <span class="radio-custom"></span>
                                <span class="radio-text">支出</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- 日付 -->
                    <div class="form-group">
                        <label for="date" class="form-label">日付</label>
                        <input type="date" 
                               id="date" 
                               name="date" 
                               class="form-input"
                               value="{{ old('date', $startDate->format('Y-m-d')) }}"
                               min="{{ $startDate->format('Y-m-d') }}"
                               max="{{ $endDate->format('Y-m-d') }}"
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <!-- カテゴリ -->
                    <div class="form-group">
                        <label for="category" class="form-label">カテゴリ</label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="">カテゴリを選択</option>
                            <optgroup label="収入" id="income-categories" style="display: none;">
                                @foreach($incomeCategories as $category)
                                    <option value="{{ $category->category }}" {{ old('category') == $category->category ? 'selected' : '' }}>
                                        {{ $category->category }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="支出" id="expense-categories" style="display: none;">
                                @foreach($expenseCategories as $category)
                                    <option value="{{ $category->category }}" {{ old('category') == $category->category ? 'selected' : '' }}>
                                        {{ $category->category }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    
                    <!-- 金額 -->
                    <div class="form-group">
                        <label for="amount" class="form-label">金額（円）</label>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               class="form-input"
                               value="{{ old('amount') }}"
                               min="1"
                               step="1"
                               placeholder="0"
                               required>
                    </div>
                </div>
                
                <!-- メモ -->
                <div class="form-group full-width">
                    <label for="memo" class="form-label">メモ</label>
                    <input type="text" 
                           id="memo" 
                           name="memo" 
                           class="form-input"
                           value="{{ old('memo') }}"
                           placeholder="具体的な購入品などを入力"
                           maxlength="255">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="btn-icon">💾</span>
                        登録
                    </button>
                </div>
            </form>
        </div>

        <!-- CSV操作 -->
        <div class="csv-actions-card">
            <h2 class="card-title">
                <span class="card-icon">📊</span>
                データ操作
            </h2>
            <div class="csv-buttons">
                <button class="csv-btn import-btn">
                    <span class="btn-icon">📤</span>
                    CSVインポート
                </button>
                <button class="csv-btn export-btn">
                    <span class="btn-icon">📥</span>
                    CSVエクスポート
                </button>
            </div>
        </div>

        <!-- 収支一覧 -->
        <div class="list-card">
            <h2 class="card-title">
                <span class="card-icon">📋</span>
                {{ $currentYear }}年{{ $currentMonth }}月の収支一覧
                <span class="record-count">({{ $oeconomicas->count() }}件)</span>
            </h2>
            
            @if($oeconomicas->count() > 0)
                <div class="table-container">
                    <table class="oeconomica-table">
                        <thead>
                            <tr>
                                <th>収支</th>
                                <th>日付</th>
                                <th>カテゴリ</th>
                                <th>金額</th>
                                <th>メモ</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($oeconomicas as $item)
                                <tr>
                                    <td>
                                        <span class="balance-badge {{ $item->balance }}">
                                            {{ $item->balance == 'income' ? '収入' : '支出' }}
                                        </span>
                                    </td>
                                    <td>{{ Carbon\Carbon::parse($item->date)->format('m/d') }}</td>
                                    <td>{{ $item->category }}</td>
                                    <td class="amount {{ $item->balance }}">
                                        {{ $item->balance == 'income' ? '+' : '-' }}{{ number_format($item->amount) }}円
                                    </td>
                                    <td>{{ $item->memo ?: '-' }}</td>
                                    <td>
                                        <button class="action-btn edit-btn" title="編集">
                                            <span>✏️</span>
                                        </button>
                                        <button class="action-btn delete-btn" title="削除">
                                            <span>🗑️</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <p class="empty-text">{{ $currentYear }}年{{ $currentMonth }}月の収支データはありません</p>
                    <p class="empty-subtext">上記のフォームから収支を登録してください</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .input-wrapper {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: calc(100vh - 80px);
        padding: 20px 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .page-title {
        font-size: 32px;
        color: #333;
        margin-bottom: 10px;
        font-weight: 300;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .title-icon {
        font-size: 36px;
    }

    .page-subtitle {
        color: #666;
        font-size: 16px;
    }

    /* アラート */
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }

    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-info {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }

    .alert-danger {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .error-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .error-list li {
        margin-bottom: 5px;
    }

    /* カード共通スタイル */
    .date-selector-card,
    .input-form-card,
    .csv-actions-card,
    .list-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .card-title {
        font-size: 20px;
        color: #333;
        margin-bottom: 20px;
        padding: 20px 30px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .card-icon {
        font-size: 24px;
    }

    /* 日付選択 */
    .date-selector-card {
        padding: 20px 30px;
    }

    .date-controls {
        display: flex;
        align-items: end;
        gap: 20px;
        margin-bottom: 15px;
    }

    .date-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .date-label {
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }

    .date-select {
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 16px;
        background: white;
        transition: border-color 0.3s ease;
    }

    .date-select:focus {
        outline: none;
        border-color: #667eea;
    }

    .switch-btn {
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .switch-btn:hover {
        transform: translateY(-2px);
    }

    .current-period {
        text-align: center;
    }

    .period-text {
        background: #f8f9fa;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        color: #667eea;
        font-weight: 500;
    }

    /* フォーム */
    .input-form {
        padding: 0 30px 30px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    .form-input,
    .form-select {
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #667eea;
    }

    /* ラジオボタン */
    .radio-group {
        display: flex;
        gap: 20px;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .radio-option input[type="radio"] {
        display: none;
    }

    .radio-custom {
        width: 18px;
        height: 18px;
        border: 2px solid #ddd;
        border-radius: 50%;
        position: relative;
        transition: all 0.3s ease;
    }

    .radio-option input[type="radio"]:checked + .radio-custom {
        border-color: #667eea;
        background: #667eea;
    }

    .radio-option input[type="radio"]:checked + .radio-custom::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
    }

    .radio-option.income input[type="radio"]:checked + .radio-custom {
        border-color: #28a745;
        background: #28a745;
    }

    .radio-option.expense input[type="radio"]:checked + .radio-custom {
        border-color: #dc3545;
        background: #dc3545;
    }

    .radio-option:hover {
        background: #f8f9fa;
    }

    .radio-text {
        font-weight: 500;
    }

    /* ボタン */
    .form-actions {
        text-align: center;
        margin-top: 30px;
    }

    .submit-btn {
        padding: 15px 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 18px;
        font-weight: 500;
        cursor: pointer;
        transition: transform 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    /* CSV操作 */
    .csv-actions-card {
        padding: 30px;
    }

    .csv-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
    }

    .csv-btn {
        padding: 12px 24px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }

    .csv-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    /* テーブル */
    .list-card {
        padding-bottom: 30px;
    }

    .record-count {
        font-size: 16px;
        color: #666;
        font-weight: normal;
    }

    .table-container {
        padding: 0 30px;
        overflow-x: auto;
    }

    .oeconomica-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .oeconomica-table th,
    .oeconomica-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .oeconomica-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .oeconomica-table td {
        font-size: 14px;
    }

    .balance-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        color: white;
    }

    .balance-badge.income {
        background: #28a745;
    }

    .balance-badge.expense {
        background: #dc3545;
    }

    .amount {
        font-weight: 600;
        text-align: right;
    }

    .amount.income {
        color: #28a745;
    }

    .amount.expense {
        color: #dc3545;
    }

    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        margin: 0 2px;
        border-radius: 4px;
        transition: background 0.3s ease;
    }

    .action-btn:hover {
        background: #f8f9fa;
    }

    /* 空状態 */
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        color: #666;
    }

    .empty-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .empty-text {
        font-size: 18px;
        margin-bottom: 10px;
        color: #333;
    }

    .empty-subtext {
        font-size: 14px;
        color: #666;
    }

    /* レスポンシブ */
    @media (max-width: 768px) {
        .container {
            padding: 0 15px;
        }

        .page-title {
            font-size: 24px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .date-controls {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }

        .csv-buttons {
            flex-direction: column;
        }

        .table-container {
            padding: 0 15px;
        }

        .oeconomica-table {
            font-size: 12px;
        }

        .oeconomica-table th,
        .oeconomica-table td {
            padding: 8px 10px;
        }
    }
</style>

<script>
    // 収支区分に応じてカテゴリ表示を切り替え
    document.querySelectorAll('input[name="balance"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const incomeCategories = document.getElementById('income-categories');
            const expenseCategories = document.getElementById('expense-categories');
            const categorySelect = document.getElementById('category');
            
            // カテゴリ選択をリセット
            categorySelect.value = '';
            
            if (this.value === 'income') {
                incomeCategories.style.display = 'block';
                expenseCategories.style.display = 'none';
            } else if (this.value === 'expense') {
                incomeCategories.style.display = 'none';
                expenseCategories.style.display = 'block';
            }
        });
    });

    // ページ読み込み時に選択された収支区分に応じてカテゴリを表示
    document.addEventListener('DOMContentLoaded', function() {
        const selectedBalance = document.querySelector('input[name="balance"]:checked');
        if (selectedBalance) {
            selectedBalance.dispatchEvent(new Event('change'));
        }
    });

    // 金額入力時の数値フォーマット
    document.getElementById('amount').addEventListener('input', function() {
        let value = this.value.replace(/[^\d]/g, '');
        if (value) {
            this.value = parseInt(value).toLocaleString();
        }
    });

    // 金額フィールドにフォーカスが当たったときにカンマを除去
    document.getElementById('amount').addEventListener('focus', function() {
        this.value = this.value.replace(/,/g, '');
    });

    // 金額フィールドからフォーカスが外れたときにカンマを追加
    document.getElementById('amount').addEventListener('blur', function() {
        if (this.value) {
            this.value = parseInt(this.value.replace(/,/g, '')).toLocaleString();
        }
    });

    // フォーム送信時にカンマを除去
    document.querySelector('.input-form').addEventListener('submit', function() {
        const amountField = document.getElementById('amount');
        amountField.value = amountField.value.replace(/,/g, '');
    });

    // CSV操作ボタンの実装（後で実装）
    document.querySelector('.import-btn').addEventListener('click', function() {
        alert('CSVインポート機能は後で実装予定です');
    });

    document.querySelector('.export-btn').addEventListener('click', function() {
        alert('CSVエクスポート機能は後で実装予定です');
    });

    // 編集・削除ボタンの実装（後で実装）
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            alert('編集機能は後で実装予定です');
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('この収支データを削除しますか？')) {
                alert('削除機能は後で実装予定です');
            }
        });
    });
</script>
@endsection