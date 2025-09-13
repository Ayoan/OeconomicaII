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