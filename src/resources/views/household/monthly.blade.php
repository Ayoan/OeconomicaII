@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">月次レポート</h2>
    
    <!-- 月選択フォーム -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="{{ route('household.monthly') }}" class="d-flex gap-2">
                <input type="month" name="month" value="{{ $targetMonth }}" class="form-control" onchange="this.form.submit()">
                <a href="{{ route('household.monthly', ['month' => date('Y-m')]) }}" class="btn btn-secondary">今月</a>
            </form>
        </div>
    </div>
    
    <!-- サマリーカード -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-success">収入</h5>
                    <h3 class="mb-1">¥{{ number_format($totalIncome) }}</h3>
                    @if($incomeChange != 0)
                        <small class="{{ $incomeChange > 0 ? 'text-success' : 'text-danger' }}">
                            前月比: {{ $incomeChange > 0 ? '+' : '' }}{{ $incomeChange }}%
                        </small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-danger">支出</h5>
                    <h3 class="mb-1">¥{{ number_format($totalExpense) }}</h3>
                    @if($expenseChange != 0)
                        <small class="{{ $expenseChange > 0 ? 'text-danger' : 'text-success' }}">
                            前月比: {{ $expenseChange > 0 ? '+' : '' }}{{ $expenseChange }}%
                        </small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">収支</h5>
                    <h3 class="mb-1 {{ $balance >= 0 ? 'text-primary' : 'text-danger' }}">
                        ¥{{ number_format($balance) }}
                    </h3>
                    <small class="text-muted">
                        貯蓄率: {{ $totalIncome > 0 ? round(($balance / $totalIncome) * 100, 1) : 0 }}%
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- グラフエリア -->
    <div class="row mb-4">
        <!-- カテゴリ別円グラフ -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">収入内訳</h5>
                </div>
                <div class="card-body">
                    @if($incomeByCategory->count() > 0)
                        <canvas id="incomePieChart" width="400" height="300"></canvas>
                    @else
                        <p class="text-center text-muted">データがありません</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">支出内訳</h5>
                </div>
                <div class="card-body">
                    @if($expenseByCategory->count() > 0)
                        <canvas id="expensePieChart" width="400" height="300"></canvas>
                    @else
                        <p class="text-center text-muted">データがありません</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- 日別推移グラフ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">日別収支推移</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" width="400" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- カテゴリ別詳細テーブル -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">収入詳細</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>カテゴリ</th>
                                <th class="text-end">金額</th>
                                <th class="text-end">割合</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomeByCategory as $category => $amount)
                                <tr>
                                    <td>{{ $category }}</td>
                                    <td class="text-end">¥{{ number_format($amount) }}</td>
                                    <td class="text-end">
                                        {{ $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">データがありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">支出詳細</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>カテゴリ</th>
                                <th class="text-end">金額</th>
                                <th class="text-end">割合</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseByCategory as $category => $amount)
                                <tr>
                                    <td>{{ $category }}</td>
                                    <td class="text-end">¥{{ number_format($amount) }}</td>
                                    <td class="text-end">
                                        {{ $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">データがありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 収入円グラフ
    @if($incomeByCategory->count() > 0)
    const incomeCtx = document.getElementById('incomePieChart').getContext('2d');
    new Chart(incomeCtx, {
        type: 'pie',
        data: {
            labels: {!! json_encode($incomeByCategory->keys()) !!},
            datasets: [{
                data: {!! json_encode($incomeByCategory->values()) !!},
                backgroundColor: {!! json_encode($incomeColors) !!},
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
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
        type: 'pie',
        data: {
            labels: {!! json_encode($expenseByCategory->keys()) !!},
            datasets: [{
                data: {!! json_encode($expenseByCategory->values()) !!},
                backgroundColor: {!! json_encode($expenseColors) !!},
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
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
        dailyLabels.push(day + '日');
        
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
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: '支出',
                    data: dailyExpenseData,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '¥' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
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