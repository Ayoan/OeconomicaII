@extends('layouts.app')

@section('title', '月次レポート - 家計簿管理システム')

@section('content')
<div class="monthly-wrapper">
    <div class="container">
        <!-- ヘッダー部分 -->
        <div class="page-header">
            <h1 class="page-title">
                <span class="title-icon">📊</span>
                月次レポート
            </h1>
            <p class="page-subtitle">{{ substr($targetMonth, 0, 4) }}年{{ substr($targetMonth, 5, 2) }}月の収支状況</p>
        </div>
        
        <!-- 月選択フォーム -->
        <div class="month-selector-card">
            <form method="GET" action="{{ route('household.monthly') }}" class="month-form">
                <div class="month-controls">
                    <a href="{{ route('household.monthly', ['month' => \Carbon\Carbon::parse($targetMonth)->subMonth()->format('Y-m')]) }}" 
                       class="nav-btn prev-btn" title="前月">
                        <span>◀</span>
                    </a>
                    <input type="month" 
                           name="month" 
                           value="{{ $targetMonth }}" 
                           class="month-input" 
                           onchange="this.form.submit()">
                    <a href="{{ route('household.monthly', ['month' => \Carbon\Carbon::parse($targetMonth)->addMonth()->format('Y-m')]) }}" 
                       class="nav-btn next-btn" title="翌月">
                        <span>▶</span>
                    </a>
                    <a href="{{ route('household.monthly', ['month' => date('Y-m')]) }}" 
                       class="today-btn">
                        今月
                    </a>
                </div>
            </form>
        </div>

        <!-- 予算サマリー（月単位予算のみ） -->
        @if(isset($budgetData) && count($budgetData) > 0)
        <div class="budget-summary-card">
            <div class="budget-header">
                <h2 class="budget-title">
                    <span class="budget-icon">💰</span>
                    今月の予算達成状況（月単位予算）
                </h2>
            </div>

            <!-- 全体サマリー -->
            <div class="budget-overall">
                <div class="budget-stat">
                    <div class="stat-label">合計予算</div>
                    <div class="stat-value">¥{{ number_format($totalBudget) }}</div>
                </div>
                <div class="budget-stat">
                    <div class="stat-label">合計実績</div>
                    <div class="stat-value {{ $totalActual > $totalBudget ? 'over' : '' }}">
                        ¥{{ number_format($totalActual) }}
                    </div>
                </div>
                <div class="budget-stat">
                    <div class="stat-label">残額</div>
                    <div class="stat-value {{ $totalRemaining < 0 ? 'negative' : 'positive' }}">
                        ¥{{ number_format($totalRemaining) }}
                    </div>
                </div>
                <div class="budget-stat">
                    <div class="stat-label">達成率</div>
                    <div class="stat-value">{{ $totalRate }}%</div>
                </div>
            </div>

            <!-- プログレスバー（全体） -->
            <div class="budget-progress-container">
                <div class="progress-bar">
                    @php
                        $progressClass = 'low';
                        if ($totalRate >= 100) $progressClass = 'over';
                        elseif ($totalRate >= 80) $progressClass = 'high';
                        elseif ($totalRate >= 50) $progressClass = 'medium';
                    @endphp
                    <div class="progress-fill {{ $progressClass }}"
                         style="width: {{ min($totalRate, 100) }}%"></div>
                </div>
                <div class="progress-label">{{ min($totalRate, 100) }}%</div>
            </div>

            <!-- カテゴリ別詳細 -->
            <div class="budget-details">
                <table class="budget-table">
                    <thead>
                        <tr>
                            <th>カテゴリ</th>
                            <th>予算</th>
                            <th>実績</th>
                            <th>残額</th>
                            <th>達成率</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($budgetData as $data)
                        <tr class="{{ $data['is_over'] ? 'over-budget' : '' }}">
                            <td>
                                <div class="category-cell">
                                    <span class="category-color" style="background-color: {{ $data['color'] }}"></span>
                                    {{ $data['category'] }}
                                </div>
                            </td>
                            <td>¥{{ number_format($data['budget']) }}</td>
                            <td>¥{{ number_format($data['actual']) }}</td>
                            <td>
                                <span class="{{ $data['remaining'] < 0 ? 'negative' : 'positive' }}">
                                    ¥{{ number_format($data['remaining']) }}
                                </span>
                            </td>
                            <td>
                                <div class="rate-cell">
                                    @php
                                        $rateClass = 'low';
                                        if ($data['rate'] >= 100) $rateClass = 'over';
                                        elseif ($data['rate'] >= 80) $rateClass = 'high';
                                        elseif ($data['rate'] >= 50) $rateClass = 'medium';
                                    @endphp
                                    <div class="mini-progress">
                                        <div class="mini-progress-fill {{ $rateClass }}"
                                             style="width: {{ min($data['rate'], 100) }}%"></div>
                                    </div>
                                    <span class="rate-text {{ $data['is_over'] ? 'over' : '' }}">
                                        {{ $data['rate'] }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="budget-empty-card">
            <div class="empty-icon">💰</div>
            <p class="empty-text">月単位予算が設定されていません</p>
            <p class="empty-subtext">設定画面から予算を設定すると、達成状況が表示されます</p>
            <a href="{{ route('household.settings') }}#budget-tab" class="setup-link">
                <span class="btn-icon">⚙️</span>
                予算を設定する
            </a>
        </div>
        @endif

        <!-- サマリーカード -->
        <div class="summary-cards">
            <div class="summary-card income-card">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3 class="card-label">収入</h3>
                    <div class="card-amount">¥{{ number_format($totalIncome) }}</div>
                    @if($incomeChange != 0)
                        <div class="card-change {{ $incomeChange > 0 ? 'positive' : 'negative' }}">
                            <span class="change-icon">{{ $incomeChange > 0 ? '📈' : '📉' }}</span>
                            前月比: {{ $incomeChange > 0 ? '+' : '' }}{{ $incomeChange }}%
                        </div>
                    @else
                        <div class="card-change neutral">
                            <span class="change-icon">➖</span>
                            前月比: 変動なし
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="summary-card expense-card">
                <div class="card-icon">💸</div>
                <div class="card-content">
                    <h3 class="card-label">支出</h3>
                    <div class="card-amount">¥{{ number_format($totalExpense) }}</div>
                    @if($expenseChange != 0)
                        <div class="card-change {{ $expenseChange > 0 ? 'negative' : 'positive' }}">
                            <span class="change-icon">{{ $expenseChange > 0 ? '📈' : '📉' }}</span>
                            前月比: {{ $expenseChange > 0 ? '+' : '' }}{{ $expenseChange }}%
                        </div>
                    @else
                        <div class="card-change neutral">
                            <span class="change-icon">➖</span>
                            前月比: 変動なし
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="summary-card balance-card {{ $balance >= 0 ? 'positive' : 'negative' }}">
                <div class="card-icon">{{ $balance >= 0 ? '✨' : '⚠️' }}</div>
                <div class="card-content">
                    <h3 class="card-label">収支</h3>
                    <div class="card-amount">¥{{ number_format($balance) }}</div>
                    <div class="card-info">
                        貯蓄率: {{ $totalIncome > 0 ? round(($balance / $totalIncome) * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>
        </div>
        
        <!-- グラフエリア -->
        <div class="charts-section">
            <!-- カテゴリ別円グラフ -->
            <div class="chart-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <span class="chart-icon">🥧</span>
                            収入内訳
                        </h3>
                    </div>
                    <div class="chart-body">
                        @if($incomeByCategory->count() > 0)
                            <div class="chart-container">
                                <canvas id="incomePieChart"></canvas>
                            </div>
                        @else
                            <div class="empty-chart">
                                <span class="empty-icon">📊</span>
                                <p>今月の収入データはありません</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <span class="chart-icon">🥧</span>
                            支出内訳
                        </h3>
                    </div>
                    <div class="chart-body">
                        @if($expenseByCategory->count() > 0)
                            <div class="chart-container">
                                <canvas id="expensePieChart"></canvas>
                            </div>
                        @else
                            <div class="empty-chart">
                                <span class="empty-icon">📊</span>
                                <p>今月の支出データはありません</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- 日別推移グラフ
            <div class="chart-card full-width">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <span class="chart-icon">📈</span>
                        日別収支推移
                    </h3>
                </div>
                <div class="chart-body">
                    <div class="chart-container daily-chart">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div> -->
            <!-- </div> -->
        </div>
        
        <!-- カテゴリ別詳細テーブル -->
        <div class="details-section">
            <div class="detail-card">
                <div class="detail-header income">
                    <h3 class="detail-title">
                        <span class="detail-icon">💰</span>
                        収入詳細
                    </h3>
                    <div class="detail-total">合計: ¥{{ number_format($totalIncome) }}</div>
                </div>
                <div class="detail-body">
                    @if($incomeByCategory->count() > 0)
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>カテゴリ</th>
                                    <th class="text-right">金額</th>
                                    <th class="text-right">割合</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incomeByCategory->sortByDesc(function($amount) { return $amount; }) as $category => $amount)
                                    <tr>
                                        <td class="category-name">{{ $category }}</td>
                                        <td class="amount">¥{{ number_format($amount) }}</td>
                                        <td class="percentage">
                                            <div class="percentage-bar">
                                                <div class="percentage-fill income" 
                                                     style="width: {{ $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0 }}%">
                                                </div>
                                                <span class="percentage-text">
                                                    {{ $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0 }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <p>データがありません</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="detail-card">
                <div class="detail-header expense">
                    <h3 class="detail-title">
                        <span class="detail-icon">💸</span>
                        支出詳細
                    </h3>
                    <div class="detail-total">合計: ¥{{ number_format($totalExpense) }}</div>
                </div>
                <div class="detail-body">
                    @if($expenseByCategory->count() > 0)
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>カテゴリ</th>
                                    <th class="text-right">金額</th>
                                    <th class="text-right">割合</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expenseByCategory->sortByDesc(function($amount) { return $amount; }) as $category => $amount)
                                    <tr>
                                        <td class="category-name">{{ $category }}</td>
                                        <td class="amount">¥{{ number_format($amount) }}</td>
                                        <td class="percentage">
                                            <div class="percentage-bar">
                                                <div class="percentage-fill expense" 
                                                     style="width: {{ $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0 }}%">
                                                </div>
                                                <span class="percentage-text">
                                                    {{ $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0 }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <p>データがありません</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .monthly-wrapper {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: calc(100vh - 80px);
        padding: 20px 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* ページヘッダー */
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

    /* 月選択 */
    .month-selector-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 30px;
    }

    .month-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    .nav-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 18px;
    }

    .nav-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .month-input {
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 16px;
        background: white;
        transition: border-color 0.3s ease;
    }

    .month-input:focus {
        outline: none;
        border-color: #667eea;
    }

    .today-btn {
        padding: 10px 20px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        text-decoration: none;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .today-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    /* サマリーカード */
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .summary-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .income-card::before {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .expense-card::before {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .balance-card.positive::before {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }

    .balance-card.negative::before {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    }

    .card-icon {
        font-size: 32px;
        margin-bottom: 15px;
    }

    .card-content {
        position: relative;
    }

    .card-label {
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .card-amount {
        font-size: 28px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .card-change {
        font-size: 13px;
        padding: 5px 10px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .card-change.positive {
        background: #d4edda;
        color: #155724;
    }

    .card-change.negative {
        background: #f8d7da;
        color: #721c24;
    }

    .card-change.neutral {
        background: #f8f9fa;
        color: #666;
    }

    .card-info {
        font-size: 14px;
        color: #666;
        margin-top: 8px;
        padding: 5px 10px;
        background: #f8f9fa;
        border-radius: 20px;
        display: inline-block;
    }

    /* グラフセクション */
    .charts-section {
        margin-bottom: 30px;
    }

    .chart-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .chart-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .chart-card.full-width {
        grid-column: 1 / -1;
    }

    .chart-header {
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .chart-title {
        font-size: 18px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .chart-icon {
        font-size: 20px;
    }

    .chart-body {
        padding: 20px;
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    .chart-container.daily-chart {
        height: 200px;
    }

    .empty-chart {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-chart .empty-icon {
        font-size: 48px;
        margin-bottom: 10px;
        display: block;
    }

    /* 詳細セクション */
    .details-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .detail-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .detail-header {
        padding: 20px;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .detail-header.income {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    }

    .detail-header.expense {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    }

    .detail-title {
        font-size: 18px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .detail-icon {
        font-size: 20px;
    }

    .detail-total {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .detail-body {
        padding: 20px;
    }

    .detail-table {
        width: 100%;
        border-collapse: collapse;
    }

    .detail-table th,
    .detail-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-table th {
        font-size: 13px;
        color: #666;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-table td {
        font-size: 14px;
    }

    .detail-table tbody tr:hover {
        background: #f8f9fa;
    }

    .category-name {
        font-weight: 500;
        color: #333;
    }

    .amount {
        text-align: right !important;
        font-weight: 600;
        color: #333;
    }

    .percentage {
        text-align: right !important;
        width: 150px;
    }

    .percentage-bar {
        position: relative;
        background: #f0f0f0;
        height: 20px;
        border-radius: 10px;
        overflow: hidden;
    }

    .percentage-fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        transition: width 0.5s ease;
    }

    .percentage-fill.income {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .percentage-fill.expense {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .percentage-text {
        position: relative;
        font-size: 11px;
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 20px;
    }

    .text-right {
        text-align: right !important;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    /* レスポンシブ */
    @media (max-width: 768px) {
        .container {
            padding: 0 15px;
        }

        .page-title {
            font-size: 24px;
        }

        .summary-cards {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .chart-row {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .details-section {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .month-controls {
            flex-wrap: wrap;
            gap: 10px;
        }

        .percentage {
            width: 100px;
        }
    }

    /* ===== 予算サマリースタイル ===== */
    .budget-summary-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 30px;
        margin-bottom: 30px;
    }

    .budget-header {
        margin-bottom: 25px;
    }

    .budget-title {
        font-size: 24px;
        font-weight: 300;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .budget-icon {
        font-size: 28px;
    }

    .budget-overall {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .budget-stat {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 600;
        color: #333;
    }

    .stat-value.over {
        color: #dc3545;
    }

    .stat-value.positive {
        color: #28a745;
    }

    .stat-value.negative {
        color: #dc3545;
    }

    .budget-progress-container {
        margin-bottom: 25px;
    }

    .progress-bar {
        width: 100%;
        height: 30px;
        background: #e9ecef;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .progress-fill {
        height: 100%;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    .progress-fill.low {
        background: linear-gradient(90deg, #28a745, #20c997);
    }

    .progress-fill.medium {
        background: linear-gradient(90deg, #ffc107, #ff9800);
    }

    .progress-fill.high {
        background: linear-gradient(90deg, #ff9800, #ff5722);
    }

    .progress-fill.over {
        background: linear-gradient(90deg, #dc3545, #c82333);
    }

    .progress-label {
        text-align: right;
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }

    .budget-details {
        overflow-x: auto;
    }

    .budget-table {
        width: 100%;
        border-collapse: collapse;
    }

    .budget-table thead {
        background: #f8f9fa;
    }

    .budget-table th {
        padding: 12px;
        text-align: left;
        font-size: 14px;
        font-weight: 500;
        color: #555;
        border-bottom: 2px solid #dee2e6;
    }

    .budget-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .budget-table tr.over-budget {
        background: #fff5f5;
    }

    .category-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .category-color {
        width: 16px;
        height: 16px;
        border-radius: 50%;
    }

    .rate-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mini-progress {
        flex: 1;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        min-width: 80px;
    }

    .mini-progress-fill {
        height: 100%;
        transition: width 0.3s ease;
    }

    .mini-progress-fill.low {
        background: #28a745;
    }

    .mini-progress-fill.medium {
        background: #ffc107;
    }

    .mini-progress-fill.high {
        background: #ff9800;
    }

    .mini-progress-fill.over {
        background: #dc3545;
    }

    .rate-text {
        font-weight: 500;
        min-width: 50px;
    }

    .rate-text.over {
        color: #dc3545;
    }

    .positive {
        color: #28a745;
    }

    .negative {
        color: #dc3545;
    }

    .budget-empty-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 40px;
        text-align: center;
        margin-bottom: 30px;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }

    .empty-text {
        font-size: 18px;
        color: #666;
        margin-bottom: 10px;
    }

    .empty-subtext {
        font-size: 14px;
        color: #999;
        margin-bottom: 20px;
    }

    .setup-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .setup-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    @media (max-width: 768px) {
        .budget-overall {
            grid-template-columns: repeat(2, 1fr);
        }

        .budget-table {
            font-size: 12px;
        }

        .budget-table th,
        .budget-table td {
            padding: 8px 6px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.jsのデフォルト設定
    Chart.defaults.font.family = "'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif";
    
    // 収入円グラフ
    @if($incomeByCategory->count() > 0)
    const incomeCtx = document.getElementById('incomePieChart').getContext('2d');
    new Chart(incomeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($incomeByCategory->keys()) !!},
            datasets: [{
                data: {!! json_encode($incomeByCategory->values()) !!},
                backgroundColor: {!! json_encode($incomeColors) !!},
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ¥' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    @endif
    
    // 支出円グラフ
    @if($expenseByCategory->count() > 0)
    const expenseCtx = document.getElementById('expensePieChart').getContext('2d');
    new Chart(expenseCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($expenseByCategory->keys()) !!},
            datasets: [{
                data: {!! json_encode($expenseByCategory->values()) !!},
                backgroundColor: {!! json_encode($expenseColors) !!},
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ¥' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    @endif
    
    // 日別推移グラフ
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyLabels = [];
    const dailyIncomeData = [];
    const dailyExpenseData = [];
    
    // 月の日数を取得
    const year = {{ substr($targetMonth, 0, 4) }};
    const month = {{ substr($targetMonth, 5, 2) }};
    const daysInMonth = new Date(year, month, 0).getDate();
    
    // 日別データの準備
    const dailyDataRaw = {!! json_encode($dailyData) !!};
    for (let i = 1; i <= daysInMonth; i++) {
        const day = i.toString().padStart(2, '0');
        dailyLabels.push(i + '日');
        
        if (dailyDataRaw[day]) {
            dailyIncomeData.push(dailyDataRaw[day].income);
            dailyExpenseData.push(dailyDataRaw[day].expense);
        } else {
            dailyIncomeData.push(0);
            dailyExpenseData.push(0);
        }
    }
    
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: dailyLabels,
            datasets: [
                {
                    label: '収入',
                    data: dailyIncomeData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: '支出',
                    data: dailyExpenseData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value.toLocaleString();
                        }
                    },
                    grid: {
                        borderDash: [5, 5]
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ¥' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection