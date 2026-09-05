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
            
            <form method="POST" action="{{ route('household.store') }}" class="input-form" id="householdForm">
                @csrf
                
                <!-- 年月の情報を隠しフィールドで送信 -->
                <input type="hidden" name="year" value="{{ $currentYear }}">
                <input type="hidden" name="month" value="{{ $currentMonth }}">
                
                <div class="form-row">
                    <!-- 収支区分 -->
                    <div class="form-group">
                        <label class="form-label">収支区分</label>
                        <div class="radio-group">
                            <label class="radio-option income">
                                <input type="radio" name="balance" value="income" 
                                    {{ (session('keep_balance') ?? old('balance')) == 'income' ? 'checked' : '' }} required>
                                <span class="radio-custom"></span>
                                <span class="radio-text">収入</span>
                            </label>
                            <label class="radio-option expense">
                                <input type="radio" name="balance" value="expense" 
                                    {{ (session('keep_balance') ?? old('balance')) == 'expense' ? 'checked' : '' }} required>
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
                               value="{{ session('keep_date') ?? old('date', Carbon\Carbon::now()->format('Y-m-d')) }}"
                               min="{{ $startDate->format('Y-m-d') }}"
                               max="{{ $endDate->format('Y-m-d') }}"
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <!-- カテゴリ -->
                    <div class="form-group">
                        <label for="category" class="form-label">カテゴリ</label>
                        <select id="category" name="category" class="form-select" required disabled>
                            <option value="">先に収支区分を選択してください</option>
                        </select>
                    </div>
                    
                    <!-- 金額 -->
                    <div class="form-group">
                        <label for="amount" class="form-label">金額（円）</label>
                        <input type="text" 
                               id="amount" 
                               name="amount" 
                               class="form-input"
                               value="{{ old('amount') }}"
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
                <!-- 合計表示 -->
                <div class="summary-row">
                    @php
                        $totalIncome = $oeconomicas->where('balance', 'income')->sum('amount');
                        $totalExpense = $oeconomicas->where('balance', 'expense')->sum('amount');
                        $balance = $totalIncome - $totalExpense;
                    @endphp
                    <div class="summary-item income">
                        <span class="summary-label">収入合計</span>
                        <span class="summary-amount">+{{ number_format($totalIncome) }}円</span>
                    </div>
                    <div class="summary-item expense">
                        <span class="summary-label">支出合計</span>
                        <span class="summary-amount">-{{ number_format($totalExpense) }}円</span>
                    </div>
                    <div class="summary-item balance {{ $balance >= 0 ? 'positive' : 'negative' }}">
                        <span class="summary-label">収支</span>
                        <span class="summary-amount">{{ $balance >= 0 ? '+' : '' }}{{ number_format($balance) }}円</span>
                    </div>
                </div>
                
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
                                        <button class="action-btn edit-btn" 
                                                title="編集" 
                                                data-id="{{ $item->id }}"
                                                data-balance="{{ $item->balance }}"
                                                data-date="{{ $item->date->format('Y-m-d') }}"
                                                data-category="{{ $item->category }}"
                                                data-amount="{{ $item->amount }}"
                                                data-memo="{{ $item->memo }}">
                                            <span>✏️</span>
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                title="削除"
                                                data-id="{{ $item->id }}"
                                                data-category="{{ $item->category }}"
                                                data-amount="{{ $item->amount }}"
                                                data-balance="{{ $item->balance }}">
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

<!-- 編集モーダル -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">✏️</span>
                収支データ編集
            </h3>
            <button class="modal-close" type="button">&times;</button>
        </div>
        
        <form id="editForm" class="modal-form">
            <input type="hidden" id="edit-id" name="id">
            
            <div class="form-row">
                <!-- 収支区分 -->
                <div class="form-group">
                    <label class="form-label">収支区分</label>
                    <div class="radio-group">
                        <label class="radio-option income">
                            <input type="radio" id="edit-balance-income" name="edit_balance" value="income" required>
                            <span class="radio-custom"></span>
                            <span class="radio-text">収入</span>
                        </label>
                        <label class="radio-option expense">
                            <input type="radio" id="edit-balance-expense" name="edit_balance" value="expense" required>
                            <span class="radio-custom"></span>
                            <span class="radio-text">支出</span>
                        </label>
                    </div>
                </div>
                
                <!-- 日付 -->
                <div class="form-group">
                    <label for="edit-date" class="form-label">日付</label>
                    <input type="date" 
                           id="edit-date" 
                           name="edit_date" 
                           class="form-input"
                           required>
                </div>
            </div>
            
            <div class="form-row">
                <!-- カテゴリ -->
                <div class="form-group">
                    <label for="edit-category" class="form-label">カテゴリ</label>
                    <select id="edit-category" name="edit_category" class="form-select" required>
                        <option value="">カテゴリを選択してください</option>
                    </select>
                </div>
                
                <!-- 金額 -->
                <div class="form-group">
                    <label for="edit-amount" class="form-label">金額（円）</label>
                    <input type="text" 
                           id="edit-amount" 
                           name="edit_amount" 
                           class="form-input"
                           placeholder="0"
                           required>
                </div>
            </div>
            
            <!-- メモ -->
            <div class="form-group full-width">
                <label for="edit-memo" class="form-label">メモ</label>
                <input type="text" 
                       id="edit-memo" 
                       name="edit_memo" 
                       class="form-input"
                       placeholder="具体的な購入品などを入力"
                       maxlength="255">
            </div>
            
            <div class="modal-actions">
                <button type="button" class="cancel-btn">キャンセル</button>
                <button type="submit" class="update-btn">
                    <span class="btn-icon">💾</span>
                    更新
                </button>
            </div>
        </form>
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

    .form-select:disabled {
        background-color: #f8f9fa;
        color: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
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

    /* サマリー */
    .summary-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        padding: 20px 30px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .summary-item {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .summary-label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .summary-amount {
        display: block;
        font-size: 18px;
        font-weight: 600;
    }

    .summary-item.income .summary-amount {
        color: #28a745;
    }

    .summary-item.expense .summary-amount {
        color: #dc3545;
    }

    .summary-item.balance.positive .summary-amount {
        color: #28a745;
    }

    .summary-item.balance.negative .summary-amount {
        color: #dc3545;
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

        .summary-row {
            grid-template-columns: 1fr;
            gap: 10px;
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

        .modal-content {
            margin: 10% auto;
            width: 95%;
        }
    }

    /* モーダル */
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(3px);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 15px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        animation: modalShow 0.3s ease-out;
    }

    @keyframes modalShow {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 20px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .modal-icon {
        font-size: 24px;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
        line-height: 1;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .modal-close:hover {
        color: #333;
        background: #f8f9fa;
    }

    .modal-form {
        padding: 20px 30px 30px;
    }

    .modal-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 30px;
    }

    .cancel-btn {
        padding: 12px 24px;
        border: 2px solid #dc3545;
        background: white;
        color: #dc3545;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .cancel-btn:hover {
        background: #dc3545;
        color: white;
    }

    .update-btn {
        padding: 12px 24px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .update-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
</style>

<!-- カテゴリデータをJavaScriptで利用できるように埋め込み -->
<script>
    // サーバーから渡されたカテゴリデータ
    const incomeCategories = @json($incomeCategories->pluck('category'));
    const expenseCategories = @json($expenseCategories->pluck('category'));
    
    console.log('収入カテゴリ:', incomeCategories);
    console.log('支出カテゴリ:', expenseCategories);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const balanceRadios = document.querySelectorAll('input[name="balance"]');
        const categorySelect = document.getElementById('category');
        
        // 収支区分変更時のイベント
        balanceRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateCategoryOptions(this.value);
            });
        });
        
        // カテゴリオプションを更新する関数
        function updateCategoryOptions(balanceType) {
            console.log('カテゴリ更新:', balanceType);
            
            // カテゴリ選択をクリア
            categorySelect.innerHTML = '<option value="">カテゴリを選択してください</option>';
            
            // 選択された収支区分に応じてカテゴリを設定
            let categories = [];
            if (balanceType === 'income') {
                categories = incomeCategories;
            } else if (balanceType === 'expense') {
                categories = expenseCategories;
            }
            
            console.log('利用可能なカテゴリ:', categories);
            
            // カテゴリオプションを追加
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                
                // old値がある場合は選択状態にする
                if (category === '{{ old("category") }}') {
                    option.selected = true;
                }
                
                categorySelect.appendChild(option);
            });
            
            // カテゴリ選択を有効化
            categorySelect.disabled = false;
        }
        
        // ページ読み込み時に選択済みの収支区分があれば処理
        const checkedBalance = document.querySelector('input[name="balance"]:checked');
        if (checkedBalance) {
            updateCategoryOptions(checkedBalance.value);
        }
        
        // 金額入力時の数値フォーマット処理
        const amountInput = document.getElementById('amount');
        
        amountInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString();
            }
        });
        
        amountInput.addEventListener('focus', function() {
            this.value = this.value.replace(/,/g, '');
        });
        
        amountInput.addEventListener('blur', function() {
            if (this.value) {
                const numericValue = this.value.replace(/,/g, '');
                if (!isNaN(numericValue) && numericValue !== '') {
                    this.value = parseInt(numericValue).toLocaleString();
                }
            }
        });
        
        // フォーム送信時の処理
        document.getElementById('householdForm').addEventListener('submit', function(e) {
            // 金額からカンマを除去
            const rawValue = amountInput.value.replace(/,/g, '');
            amountInput.value = rawValue;
            
            // バリデーション
            if (!rawValue || isNaN(rawValue) || parseInt(rawValue) < 1) {
                e.preventDefault();
                alert('正しい金額を入力してください。');
                amountInput.focus();
                return;
            }
            
            if (!categorySelect.value) {
                e.preventDefault();
                alert('カテゴリを選択してください。');
                categorySelect.focus();
                return;
            }
        });
    });

    // CSV操作ボタンの実装
    document.querySelector('.import-btn')?.addEventListener('click', function() {
        window.location.href = '/household/import';
    });

    document.querySelector('.export-btn')?.addEventListener('click', function() {
        // 現在の年月を取得
        const currentYear = {{ $currentYear }};
        const currentMonth = {{ $currentMonth }};
        
        // エクスポートURLを構築
        const exportUrl = `/household/export-csv?year=${currentYear}&month=${currentMonth}`;
        
        // ダウンロード実行
        window.location.href = exportUrl;
    });

    // 編集・削除ボタンの実装
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openEditModal(this);
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteOeconomica(this);
        });
    });

    // 編集モーダルを開く
    function openEditModal(button) {
        const modal = document.getElementById('editModal');
        const form = document.getElementById('editForm');
        
        // データを取得
        const id = button.getAttribute('data-id');
        const balance = button.getAttribute('data-balance');
        const date = button.getAttribute('data-date');
        const category = button.getAttribute('data-category');
        const amount = button.getAttribute('data-amount');
        const memo = button.getAttribute('data-memo');
        
        // フォームにデータを設定
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-date').value = date;
        document.getElementById('edit-memo').value = memo || '';
        
        // 収支区分を設定
        if (balance === 'income') {
            document.getElementById('edit-balance-income').checked = true;
        } else {
            document.getElementById('edit-balance-expense').checked = true;
        }
        
        // カテゴリを更新
        updateEditCategories(balance, category);
        
        // 金額を設定（カンマ付きで表示）
        document.getElementById('edit-amount').value = parseInt(amount).toLocaleString();
        
        // モーダルを表示
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // 編集用カテゴリ更新
    function updateEditCategories(balanceType, selectedCategory = '') {
        const categorySelect = document.getElementById('edit-category');
        
        // カテゴリ選択をクリア
        categorySelect.innerHTML = '<option value="">カテゴリを選択してください</option>';
        
        // 選択された収支区分に応じてカテゴリを設定
        let categories = [];
        if (balanceType === 'income') {
            categories = incomeCategories;
        } else if (balanceType === 'expense') {
            categories = expenseCategories;
        }
        
        // カテゴリオプションを追加
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            
            if (category === selectedCategory) {
                option.selected = true;
            }
            
            categorySelect.appendChild(option);
        });
    }

    // 編集フォームの収支区分変更イベント
    document.querySelectorAll('input[name="edit_balance"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateEditCategories(this.value);
        });
    });

    // モーダルを閉じる
    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // モーダル関連イベント
    document.querySelector('.modal-close').addEventListener('click', closeEditModal);
    document.querySelector('.cancel-btn').addEventListener('click', closeEditModal);
    
    // モーダル外クリックで閉じる
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // 編集フォーム送信
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('edit-id').value;
        const balance = document.querySelector('input[name="edit_balance"]:checked').value;
        const date = document.getElementById('edit-date').value;
        const category = document.getElementById('edit-category').value;
        const amount = document.getElementById('edit-amount').value.replace(/,/g, '');
        const memo = document.getElementById('edit-memo').value;
        
        // バリデーション
        if (!balance || !date || !category || !amount) {
            alert('必須項目を入力してください。');
            return;
        }
        
        if (isNaN(amount) || parseInt(amount) < 1) {
            alert('正しい金額を入力してください。');
            return;
        }
        
        // 更新処理
        updateOeconomica(id, {
            balance: balance,
            date: date,
            category: category,
            amount: parseInt(amount),
            memo: memo
        });
    });

    // データ更新
    function updateOeconomica(id, data) {
        const updateBtn = document.querySelector('.update-btn');
        const originalText = updateBtn.innerHTML;
        updateBtn.innerHTML = '<span class="btn-icon">⏳</span>更新中...';
        updateBtn.disabled = true;
        
        // CSRFトークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            alert('セキュリティトークンが見つかりません。ページを再読み込みしてください。');
            updateBtn.innerHTML = originalText;
            updateBtn.disabled = false;
            return;
        }
        
        fetch(`/household/update/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                // HTTPエラーの場合、レスポンステキストを取得
                return response.text().then(text => {
                    console.error('Server response:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                closeEditModal();
                location.reload(); // ページを再読み込みして最新データを表示
            } else {
                alert(result.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            alert('通信エラーが発生しました: ' + error.message);
        })
        .finally(() => {
            updateBtn.innerHTML = originalText;
            updateBtn.disabled = false;
        });
    }

    // データ削除
    function deleteOeconomica(button) {
        const id = button.getAttribute('data-id');
        const category = button.getAttribute('data-category');
        const amount = button.getAttribute('data-amount');
        const balance = button.getAttribute('data-balance');
        const balanceText = balance === 'income' ? '収入' : '支出';
        
        const message = `以下のデータを削除しますか？\n\n${balanceText}: ${category}\n金額: ${parseInt(amount).toLocaleString()}円`;
        
        if (!confirm(message)) {
            return;
        }
        
        // CSRFトークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            alert('セキュリティトークンが見つかりません。ページを再読み込みしてください。');
            return;
        }
        
        fetch(`/household/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                // HTTPエラーの場合、レスポンステキストを取得
                return response.text().then(text => {
                    console.error('Server response:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                alert(result.message);
                location.reload(); // ページを再読み込みして最新データを表示
            } else {
                alert(result.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            alert('通信エラーが発生しました: ' + error.message);
        });
    }

    // 編集モーダルの金額入力処理
    document.getElementById('edit-amount').addEventListener('input', function() {
        let value = this.value.replace(/[^\d]/g, '');
        if (value) {
            this.value = parseInt(value).toLocaleString();
        }
    });

    document.getElementById('edit-amount').addEventListener('focus', function() {
        this.value = this.value.replace(/,/g, '');
    });

    document.getElementById('edit-amount').addEventListener('blur', function() {
        if (this.value) {
            const numericValue = this.value.replace(/,/g, '');
            if (!isNaN(numericValue) && numericValue !== '') {
                this.value = parseInt(numericValue).toLocaleString();
            }
        }
    });

</script>
@endsection