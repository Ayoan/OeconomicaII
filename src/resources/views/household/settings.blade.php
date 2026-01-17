@extends('layouts.app')

@section('title', '設定 - 家計簿管理システム')

@section('content')
<div class="settings-wrapper">
    <div class="container">
        <!-- ヘッダー部分 -->
        <div class="page-header">
            <h1 class="page-title">
                <span class="title-icon">⚙️</span>
                設定
            </h1>
            <p class="page-subtitle">カテゴリの管理やアプリの設定を行います</p>
        </div>

        <!-- 成功・エラーメッセージ -->
        @if (session('success'))
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                {{ session('success') }}
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

        <!-- タブナビゲーション -->
        <div class="tab-navigation">
            <button class="tab-button active" data-tab="category-tab" onclick="switchTab('category-tab')">
                <span class="tab-icon">🏷️</span>
                カテゴリ管理
            </button>
            <button class="tab-button" data-tab="subscription-tab" onclick="switchTab('subscription-tab')">
                <span class="tab-icon">🔄</span>
                サブスクリプション管理
            </button>
            <button class="tab-button" data-tab="budget-tab" onclick="switchTab('budget-tab')">
                <span class="tab-icon">💰</span>
                予算管理
            </button>
        </div>

        <!-- カテゴリ管理タブ -->
        <div id="category-tab" class="tab-content active">
        <!-- カテゴリ管理カード -->
        <div class="settings-card">
            <div class="card-header">
                <h2 class="card-title">
                    <span class="card-icon">🏷️</span>
                    カテゴリ管理
                </h2>
                <button class="reset-btn" onclick="resetCategories()">
                    <span class="btn-icon">🔄</span>
                    デフォルトにリセット
                </button>
            </div>

            <div class="category-management">
                <!-- 収入カテゴリ -->
                <div class="category-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <span class="income-badge">収入</span>
                            カテゴリ
                        </h3>
                        <button class="add-category-btn" onclick="openAddModal('income')">
                            <span class="btn-icon">➕</span>
                            追加
                        </button>
                    </div>
                    
                    <div class="category-list" id="income-categories" data-type="income">
                        @foreach($incomeCategories as $category)
                            <div class="category-item" data-id="{{ $category->id }}" draggable="true">
                                <div class="drag-handle">⋮⋮</div>
                                <div class="category-color" style="background-color: {{ $category->color }}"></div>
                                <div class="category-name">{{ $category->category }}</div>
                                <div class="category-actions">
                                    <button class="action-btn edit-btn" 
                                            onclick="openEditModal({{ $category->id }}, '{{ $category->category }}', '{{ $category->color }}')"
                                            title="編集">
                                        ✏️
                                    </button>
                                    <button class="action-btn delete-btn" 
                                            onclick="deleteCategory({{ $category->id }}, '{{ $category->category }}')"
                                            title="削除">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 支出カテゴリ -->
                <div class="category-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <span class="expense-badge">支出</span>
                            カテゴリ
                        </h3>
                        <button class="add-category-btn" onclick="openAddModal('expense')">
                            <span class="btn-icon">➕</span>
                            追加
                        </button>
                    </div>
                    
                    <div class="category-list" id="expense-categories" data-type="expense">
                        @foreach($expenseCategories as $category)
                            <div class="category-item" data-id="{{ $category->id }}" draggable="true">
                                <div class="drag-handle">⋮⋮</div>
                                <div class="category-color" style="background-color: {{ $category->color }}"></div>
                                <div class="category-name">{{ $category->category }}</div>
                                <div class="category-actions">
                                    <button class="action-btn edit-btn" 
                                            onclick="openEditModal({{ $category->id }}, '{{ $category->category }}', '{{ $category->color }}')"
                                            title="編集">
                                        ✏️
                                    </button>
                                    <button class="action-btn delete-btn" 
                                            onclick="deleteCategory({{ $category->id }}, '{{ $category->category }}')"
                                            title="削除">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        </div>
        <!-- カテゴリ管理タブ終了 -->

        <!-- サブスクリプション管理タブ -->
        <div id="subscription-tab" class="tab-content" style="display: none;">
            <!-- 為替レート表示 -->
            <div class="exchange-rate-card">
                <div class="exchange-rate-info">
                    <span class="exchange-rate-label">💱 現在の為替レート:</span>
                    <span class="exchange-rate-value">1 USD = ¥{{ number_format($currentExchangeRate, 2) }}</span>
                    <span class="exchange-rate-note">（自動取得・1時間ごとに更新）</span>
                </div>
            </div>

            <!-- 追加ボタン -->
            <div class="add-button-container">
                <button class="add-btn-subscription" onclick="openAddSubscriptionModal()">
                    <span class="btn-icon">➕</span>
                    サブスクリプションを追加
                </button>
            </div>

            <!-- サブスクリプション一覧 -->
            @if(isset($subscriptions) && $subscriptions->count() > 0)
                <div class="subscriptions-grid">
                    @foreach($subscriptions as $subscription)
                        <div class="subscription-card {{ $subscription->is_active ? 'active' : 'inactive' }}">
                            <div class="subscription-header">
                                <h3 class="subscription-name">{{ $subscription->subscription }}</h3>
                                <span class="currency-badge {{ strtolower($subscription->currency) }}">
                                    {{ $subscription->currency }}
                                </span>
                                <div class="toggle-container">
                                    <label class="toggle-switch {{ $subscription->is_active ? 'active' : '' }}"
                                           onclick="toggleSubscription({{ $subscription->id }}, this)">
                                        <span class="toggle-label">{{ $subscription->is_active ? '有効' : '無効' }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="subscription-body">
                                <div class="subscription-amount">
                                    {{ $subscription->formatted_amount }}
                                </div>

                                @if($subscription->currency === 'USD')
                                <div class="exchange-info-card">
                                    <span class="detail-label">円換算:</span>
                                    <span class="detail-value">約¥{{ number_format($subscription->jpy_amount) }}</span>
                                </div>
                                @endif

                                <div class="subscription-details">
                                    <div class="detail-item">
                                        <span class="detail-label">カテゴリ</span>
                                        <span class="category-badge-sub">{{ $subscription->category }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <span class="detail-label">実行日</span>
                                        <span class="detail-value">{{ $subscription->execution_day_text }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <span class="detail-label">次回実行</span>
                                        <span class="detail-value">{{ $subscription->next_execution_date->format('Y年m月d日') }}</span>
                                    </div>

                                    @if($subscription->payday)
                                        <div class="detail-item">
                                            <span class="detail-label">最終実行</span>
                                            <span class="detail-value">{{ $subscription->payday->format('Y年m月d日') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="subscription-footer">
                                <button class="action-btn-sub edit-btn-sub"
                                        onclick="openEditSubscriptionModal({{ $subscription->id }}, '{{ $subscription->subscription }}', '{{ $subscription->category }}', {{ $subscription->amount }}, {{ $subscription->day }}, '{{ $subscription->currency }}')">
                                    <span>✏️</span> 編集
                                </button>
                                <button class="action-btn-sub delete-btn-sub"
                                        onclick="deleteSubscription({{ $subscription->id }}, '{{ $subscription->subscription }}')">
                                    <span>🗑️</span> 削除
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- 空状態 -->
                <div class="empty-state">
                    <div class="empty-icon">🔄</div>
                    <p class="empty-text">まだサブスクリプションが登録されていません</p>
                    <p class="empty-subtext">定期的な支出を登録して、自動で家計簿に記録しましょう</p>
                    <button class="empty-add-btn" onclick="openAddSubscriptionModal()">
                        <span class="btn-icon">➕</span>
                        サブスクリプションを追加
                    </button>
                </div>
            @endif
        </div>
        <!-- サブスクリプション管理タブ終了 -->

        <!-- 予算管理タブ -->
        <div id="budget-tab" class="tab-content" style="display: none;">
            <div class="settings-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span class="card-icon">💰</span>
                        予算管理
                    </h2>
                    <p class="card-subtitle">カテゴリ別に月単位または年単位で予算を設定</p>
                </div>

                <!-- 予算一覧 -->
                @if(isset($expenseCategories) && $expenseCategories->count() > 0)
                    <div class="budget-list">
                        @foreach($expenseCategories as $category)
                            @php
                                $budget = isset($budgets) ? $budgets->get($category->category) : null;
                            @endphp
                            <div class="budget-item">
                                <div class="budget-item-header">
                                    <div class="category-color" style="background-color: {{ $category->color }}"></div>
                                    <div class="category-name">{{ $category->category }}</div>
                                    @if($budget)
                                        <span class="period-badge {{ $budget->period }}">
                                            {{ $budget->period_text }}
                                        </span>
                                    @endif
                                </div>

                                <form class="budget-form" onsubmit="saveBudget(event, {{ $budget ? $budget->id : 'null' }}, '{{ $category->category }}')">
                                    <div class="budget-input-group">
                                        <label class="budget-label">予算額</label>
                                        <div class="amount-input-wrapper">
                                            <span class="currency-symbol">¥</span>
                                            <input type="text"
                                                   class="budget-amount-input"
                                                   name="amount"
                                                   value="{{ $budget ? number_format($budget->amount) : '' }}"
                                                   placeholder="0"
                                                   oninput="formatBudgetAmount(this)">
                                        </div>
                                    </div>

                                    <div class="budget-period-group">
                                        <label class="budget-label">期間</label>
                                        <div class="period-radio-group">
                                            <label class="period-radio">
                                                <input type="radio"
                                                       name="period_{{ $category->id }}"
                                                       value="monthly"
                                                       {{ !$budget || $budget->period === 'monthly' ? 'checked' : '' }}>
                                                <span class="radio-label">月単位</span>
                                            </label>
                                            <label class="period-radio">
                                                <input type="radio"
                                                       name="period_{{ $category->id }}"
                                                       value="yearly"
                                                       {{ $budget && $budget->period === 'yearly' ? 'checked' : '' }}>
                                                <span class="radio-label">年単位</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="budget-actions">
                                        <button type="submit" class="save-budget-btn">
                                            <span class="btn-icon">💾</span>
                                            保存
                                        </button>
                                        @if($budget)
                                            <button type="button"
                                                    class="delete-budget-btn"
                                                    onclick="deleteBudget(event, {{ $budget->id }}, '{{ $category->category }}')">
                                                <span class="btn-icon">🗑️</span>
                                                削除
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- 空状態 -->
                    <div class="empty-state">
                        <div class="empty-icon">💰</div>
                        <p class="empty-text">まだ支出カテゴリが登録されていません</p>
                        <p class="empty-subtext">カテゴリを追加してから予算を設定してください</p>
                    </div>
                @endif
            </div>
        </div>
        <!-- 予算管理タブ終了 -->

    </div>
</div>

<!-- カテゴリ追加モーダル -->
<div id="addModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">➕</span>
                カテゴリ追加
            </h3>
            <button class="modal-close" type="button" onclick="closeAddModal()">&times;</button>
        </div>
        
        <form id="addForm" class="modal-form" onsubmit="addCategory(event)">
            <input type="hidden" id="add-type" name="type">
            
            <div class="form-group">
                <label for="add-category" class="form-label">カテゴリ名</label>
                <input type="text" 
                       id="add-category" 
                       name="category" 
                       class="form-input"
                       placeholder="例: 娯楽費"
                       required>
            </div>
            
            <div class="form-group">
                <label for="add-color" class="form-label">色</label>
                <div class="color-picker-wrapper">
                    <input type="color" 
                           id="add-color" 
                           name="color" 
                           class="color-picker"
                           value="#667eea"
                           required>
                    <input type="text" 
                           id="add-color-text" 
                           class="color-text"
                           value="#667eea"
                           pattern="^#[0-9A-Fa-f]{6}$"
                           required>
                </div>
                <div class="color-presets">
                    <button type="button" class="color-preset" style="background: #667eea" onclick="selectColor('#667eea')"></button>
                    <button type="button" class="color-preset" style="background: #764ba2" onclick="selectColor('#764ba2')"></button>
                    <button type="button" class="color-preset" style="background: #f093fb" onclick="selectColor('#f093fb')"></button>
                    <button type="button" class="color-preset" style="background: #4facfe" onclick="selectColor('#4facfe')"></button>
                    <button type="button" class="color-preset" style="background: #43e97b" onclick="selectColor('#43e97b')"></button>
                    <button type="button" class="color-preset" style="background: #fa709a" onclick="selectColor('#fa709a')"></button>
                    <button type="button" class="color-preset" style="background: #fee140" onclick="selectColor('#fee140')"></button>
                    <button type="button" class="color-preset" style="background: #30cfd0" onclick="selectColor('#30cfd0')"></button>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeAddModal()">キャンセル</button>
                <button type="submit" class="submit-btn">
                    <span class="btn-icon">💾</span>
                    追加
                </button>
            </div>
        </form>
    </div>
</div>

<!-- カテゴリ編集モーダル -->
<div id="editModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">✏️</span>
                カテゴリ編集
            </h3>
            <button class="modal-close" type="button" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form id="editForm" class="modal-form" onsubmit="updateCategory(event)">
            <input type="hidden" id="edit-id" name="id">
            
            <div class="form-group">
                <label for="edit-category" class="form-label">カテゴリ名</label>
                <input type="text" 
                       id="edit-category" 
                       name="category" 
                       class="form-input"
                       required>
            </div>
            
            <div class="form-group">
                <label for="edit-color" class="form-label">色</label>
                <div class="color-picker-wrapper">
                    <input type="color" 
                           id="edit-color" 
                           name="color" 
                           class="color-picker"
                           required>
                    <input type="text" 
                           id="edit-color-text" 
                           class="color-text"
                           pattern="^#[0-9A-Fa-f]{6}$"
                           required>
                </div>
                <div class="color-presets">
                    <button type="button" class="color-preset" style="background: #667eea" onclick="selectEditColor('#667eea')"></button>
                    <button type="button" class="color-preset" style="background: #764ba2" onclick="selectEditColor('#764ba2')"></button>
                    <button type="button" class="color-preset" style="background: #f093fb" onclick="selectEditColor('#f093fb')"></button>
                    <button type="button" class="color-preset" style="background: #4facfe" onclick="selectEditColor('#4facfe')"></button>
                    <button type="button" class="color-preset" style="background: #43e97b" onclick="selectEditColor('#43e97b')"></button>
                    <button type="button" class="color-preset" style="background: #fa709a" onclick="selectEditColor('#fa709a')"></button>
                    <button type="button" class="color-preset" style="background: #fee140" onclick="selectEditColor('#fee140')"></button>
                    <button type="button" class="color-preset" style="background: #30cfd0" onclick="selectEditColor('#30cfd0')"></button>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeEditModal()">キャンセル</button>
                <button type="submit" class="submit-btn">
                    <span class="btn-icon">💾</span>
                    更新
                </button>
            </div>
        </form>
    </div>
</div>

<!-- サブスクリプション追加モーダル -->
<div id="addSubscriptionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">➕</span>
                サブスクリプション追加
            </h3>
            <button class="modal-close" type="button" onclick="closeAddSubscriptionModal()">&times;</button>
        </div>

        <form id="addSubscriptionForm" class="modal-form" onsubmit="submitAddSubscription(event)">
            @csrf

            <div class="form-group">
                <label for="add-subscription-name" class="form-label">サブスクリプション名</label>
                <input type="text"
                       id="add-subscription-name"
                       name="subscription"
                       class="form-input"
                       placeholder="例: Netflix, Spotify"
                       required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="add-subscription-category" class="form-label">カテゴリ</label>
                    <select id="add-subscription-category" name="category" class="form-select" required>
                        <option value="">選択してください</option>
                        @if(isset($expenseCategories))
                            @foreach($expenseCategories as $category)
                                <option value="{{ $category->category }}">{{ $category->category }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-group">
                    <label for="add-subscription-day" class="form-label">実行日</label>
                    <select id="add-subscription-day" name="day" class="form-select" required>
                        <option value="">選択してください</option>
                        @for($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}">{{ $i }}日</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="add-subscription-amount" class="form-label">金額</label>
                <div class="amount-currency-group">
                    <input type="number"
                           id="add-subscription-amount"
                           name="amount"
                           class="form-input amount-input"
                           placeholder="0"
                           step="0.01"
                           min="1"
                           required>

                    <select id="add-subscription-currency"
                            name="currency"
                            class="form-select currency-select"
                            required>
                        <option value="JPY">円 (¥)</option>
                        <option value="USD">ドル ($)</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeAddSubscriptionModal()">キャンセル</button>
                <button type="submit" class="submit-btn">
                    <span class="btn-icon">💾</span>
                    登録
                </button>
            </div>
        </form>
    </div>
</div>

<!-- サブスクリプション編集モーダル -->
<div id="editSubscriptionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="modal-icon">✏️</span>
                サブスクリプション編集
            </h3>
            <button class="modal-close" type="button" onclick="closeEditSubscriptionModal()">&times;</button>
        </div>

        <form id="editSubscriptionForm" class="modal-form" onsubmit="submitEditSubscription(event)">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-subscription-id" name="id">

            <div class="form-group">
                <label for="edit-subscription-name" class="form-label">サブスクリプション名</label>
                <input type="text"
                       id="edit-subscription-name"
                       name="subscription"
                       class="form-input"
                       required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-subscription-category" class="form-label">カテゴリ</label>
                    <select id="edit-subscription-category" name="category" class="form-select" required>
                        <option value="">選択してください</option>
                        @if(isset($expenseCategories))
                            @foreach($expenseCategories as $category)
                                <option value="{{ $category->category }}">{{ $category->category }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit-subscription-day" class="form-label">実行日</label>
                    <select id="edit-subscription-day" name="day" class="form-select" required>
                        <option value="">選択してください</option>
                        @for($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}">{{ $i }}日</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="edit-subscription-amount" class="form-label">金額</label>
                <div class="amount-currency-group">
                    <input type="number"
                           id="edit-subscription-amount"
                           name="amount"
                           class="form-input amount-input"
                           step="0.01"
                           min="1"
                           required>

                    <select id="edit-subscription-currency"
                            name="currency"
                            class="form-select currency-select"
                            required>
                        <option value="JPY">円 (¥)</option>
                        <option value="USD">ドル ($)</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeEditSubscriptionModal()">キャンセル</button>
                <button type="submit" class="submit-btn">
                    <span class="btn-icon">💾</span>
                    更新
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .settings-wrapper {
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

    /* タブナビゲーション */
    .tab-navigation {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .tab-button {
        flex: 1;
        padding: 15px 20px;
        background: transparent;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 500;
        color: #666;
    }

    .tab-button:hover {
        background: #f8f9fa;
        color: #333;
    }

    .tab-button.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .tab-icon {
        font-size: 20px;
    }

    /* タブコンテンツ */
    .tab-content {
        animation: fadeIn 0.3s ease-in;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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

    /* 設定カード */
    .settings-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-title {
        font-size: 20px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .card-icon {
        font-size: 24px;
    }

    .reset-btn {
        padding: 10px 20px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .reset-btn:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    /* カテゴリ管理 */
    .category-management {
        padding: 30px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .category-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .section-title {
        font-size: 18px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .income-badge {
        background: #28a745;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .expense-badge {
        background: #dc3545;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .add-category-btn {
        padding: 8px 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
    }

    .add-category-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* カテゴリリスト */
    .category-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-height: 100px;
    }

    .category-item {
        background: white;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: move;
        transition: all 0.3s ease;
    }

    .category-item:hover {
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
    }

    .category-item.dragging {
        opacity: 0.5;
    }

    .category-item.drag-over {
        border-color: #667eea;
        border-style: dashed;
        background: #f0f4ff;
    }

    .drag-handle {
        color: #999;
        font-size: 16px;
        cursor: grab;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    .category-color {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid #e1e8ed;
    }

    .category-name {
        flex: 1;
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    .category-actions {
        display: flex;
        gap: 5px;
    }

    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        transition: background 0.3s ease;
        font-size: 16px;
    }

    .action-btn:hover {
        background: #f8f9fa;
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
        width: 90%;
        max-width: 500px;
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

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        color: #333;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .form-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #667eea;
    }

    /* カラーピッカー */
    .color-picker-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .color-picker {
        width: 60px;
        height: 45px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        cursor: pointer;
    }

    .color-text {
        flex: 1;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 16px;
        font-family: monospace;
        transition: border-color 0.3s ease;
    }

    .color-text:focus {
        outline: none;
        border-color: #667eea;
    }

    .color-presets {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .color-preset {
        width: 36px;
        height: 36px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .color-preset:hover {
        transform: scale(1.1);
        border-color: #667eea;
    }

    /* モーダルアクション */
    .modal-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 30px;
    }

    .cancel-btn {
        padding: 12px 24px;
        border: 2px solid #6c757d;
        background: white;
        color: #6c757d;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .cancel-btn:hover {
        background: #6c757d;
        color: white;
    }

    .submit-btn {
        padding: 12px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    /* サブスクリプション管理スタイル */
    .add-button-container {
        text-align: center;
        margin-bottom: 30px;
    }

    .add-btn-subscription {
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

    .add-btn-subscription:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .subscriptions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .subscription-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .subscription-card:hover {
        transform: translateY(-5px);
    }

    .subscription-card.inactive {
        opacity: 0.6;
    }

    .subscription-header {
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    /* 為替レート表示 */
    .exchange-rate-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .exchange-rate-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
    }

    .exchange-rate-label {
        font-weight: 500;
        font-size: 14px;
    }

    .exchange-rate-value {
        font-weight: 600;
        font-size: 18px;
    }

    .exchange-rate-note {
        font-size: 12px;
        opacity: 0.9;
    }

    /* 通貨バッジ */
    .currency-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        color: white;
        background: rgba(255, 255, 255, 0.3);
    }

    .currency-badge.jpy {
        background: rgba(40, 167, 69, 0.8);
    }

    .currency-badge.usd {
        background: rgba(0, 123, 255, 0.8);
    }

    /* 金額と通貨選択 */
    .amount-currency-group {
        display: flex;
        gap: 10px;
    }

    .amount-input {
        flex: 2;
    }

    .currency-select {
        flex: 1;
        min-width: 120px;
    }

    /* 円換算情報 */
    .exchange-info-card {
        background: #f8f9fa;
        padding: 10px 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .exchange-info-card .detail-value {
        color: #667eea;
        font-weight: 600;
    }

    .subscription-name {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    .toggle-container {
        display: flex;
        align-items: center;
    }

    .toggle-switch {
        position: relative;
        width: 60px;
        height: 28px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 14px;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        padding: 0 5px;
    }

    .toggle-switch.active {
        background: #28a745;
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        top: 3px;
        left: 3px;
        transition: left 0.3s;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch.active::after {
        left: 35px;
    }

    .toggle-label {
        position: absolute;
        right: -50px;
        font-size: 12px;
        white-space: nowrap;
        font-weight: 500;
    }

    .subscription-body {
        padding: 20px;
    }

    .subscription-amount {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 15px;
        text-align: center;
    }

    .subscription-details {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }

    .detail-value {
        font-size: 14px;
        color: #333;
        font-weight: 600;
    }

    .category-badge-sub {
        padding: 4px 12px;
        background: #667eea;
        color: white;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .subscription-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .action-btn-sub {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .edit-btn-sub {
        background: #28a745;
        color: white;
    }

    .edit-btn-sub:hover {
        background: #218838;
    }

    .delete-btn-sub {
        background: #dc3545;
        color: white;
    }

    .delete-btn-sub:hover {
        background: #c82333;
    }

    .empty-state {
        text-align: center;
        padding: 60px 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
        margin-bottom: 30px;
    }

    .empty-add-btn {
        padding: 12px 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: transform 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .empty-add-btn:hover {
        transform: translateY(-2px);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
        background: white;
    }

    .form-select:focus {
        outline: none;
        border-color: #667eea;
    }

    /* レスポンシブ */
    @media (max-width: 768px) {
        .category-management {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
        }

        .modal-content {
            width: 95%;
            margin: 10% auto;
        }

        .card-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .reset-btn {
            width: 100%;
            justify-content: center;
        }

        .tab-navigation {
            flex-direction: column;
            gap: 5px;
        }

        .tab-button {
            width: 100%;
        }

        .subscriptions-grid {
            grid-template-columns: 1fr;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .page-title {
            font-size: 24px;
        }
    }

    /* ===== 予算管理スタイル ===== */
    .budget-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }

    .budget-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
    }

    .budget-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: #667eea;
    }

    .budget-item-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }

    .budget-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 15px;
        align-items: end;
    }

    .budget-input-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .budget-label {
        font-size: 14px;
        font-weight: 500;
        color: #555;
    }

    .amount-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .currency-symbol {
        position: absolute;
        left: 12px;
        font-size: 16px;
        color: #666;
        font-weight: 500;
    }

    .budget-amount-input {
        width: 100%;
        padding: 10px 12px 10px 32px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .budget-amount-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .budget-period-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .period-radio-group {
        display: flex;
        gap: 12px;
    }

    .period-radio {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        padding: 8px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .period-radio:hover {
        border-color: #667eea;
        background: #f8f9ff;
    }

    .period-radio input[type="radio"] {
        margin: 0;
        cursor: pointer;
    }

    .period-radio input[type="radio"]:checked ~ .radio-label {
        color: #667eea;
        font-weight: 600;
    }

    .period-radio:has(input[type="radio"]:checked) {
        border-color: #667eea;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }

    .radio-label {
        font-size: 14px;
        color: #555;
        transition: all 0.3s ease;
    }

    .budget-actions {
        display: flex;
        gap: 8px;
    }

    .save-budget-btn {
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .save-budget-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .delete-budget-btn {
        padding: 10px 20px;
        background: white;
        color: #dc3545;
        border: 2px solid #dc3545;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .delete-budget-btn:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }

    .period-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        color: white;
    }

    .period-badge.monthly {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .period-badge.yearly {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    @media (max-width: 768px) {
        .budget-form {
            grid-template-columns: 1fr;
        }

        .period-radio-group {
            flex-direction: row;
        }

        .budget-actions {
            flex-direction: column;
        }

        .save-budget-btn,
        .delete-budget-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    // CSRFトークン
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // カラーピッカー連動（追加モーダル）
    document.getElementById('add-color')?.addEventListener('input', function() {
        document.getElementById('add-color-text').value = this.value;
    });

    document.getElementById('add-color-text')?.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            document.getElementById('add-color').value = this.value;
        }
    });

    // カラーピッカー連動（編集モーダル）
    document.getElementById('edit-color')?.addEventListener('input', function() {
        document.getElementById('edit-color-text').value = this.value;
    });

    document.getElementById('edit-color-text')?.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            document.getElementById('edit-color').value = this.value;
        }
    });

    // プリセット色選択（追加）
    function selectColor(color) {
        document.getElementById('add-color').value = color;
        document.getElementById('add-color-text').value = color;
    }

    // プリセット色選択（編集）
    function selectEditColor(color) {
        document.getElementById('edit-color').value = color;
        document.getElementById('edit-color-text').value = color;
    }

    // 追加モーダルを開く
    function openAddModal(type) {
        document.getElementById('add-type').value = type;
        document.getElementById('addModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // 追加モーダルを閉じる
    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('addForm').reset();
    }

    // 編集モーダルを開く
    function openEditModal(id, name, color) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-category').value = name;
        document.getElementById('edit-color').value = color;
        document.getElementById('edit-color-text').value = color;
        document.getElementById('editModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // 編集モーダルを閉じる
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('editForm').reset();
    }

    // モーダル外クリックで閉じる
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // カテゴリ追加
    async function addCategory(event) {
        event.preventDefault();
        
        const submitBtn = event.target.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>追加中...';
        submitBtn.disabled = true;
        
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch('/household/category/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                alert(result.message);
                closeAddModal();
                location.reload();
            } else {
                alert(result.error || 'エラーが発生しました');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    // カテゴリ削除
    async function deleteCategory(id, name) {
        if (!confirm(`「${name}」を削除しますか？\n\nこのカテゴリを使用している収支データがある場合は削除できません。`)) {
            return;
        }
        
        try {
            const response = await fetch(`/household/category/delete/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert(result.error || 'エラーが発生しました');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        }
    }

    // カテゴリリセット
    async function resetCategories() {
        if (!confirm('全てのカテゴリをデフォルト状態にリセットしますか？\n\n現在のカスタムカテゴリは全て削除されます。\n※収支データは削除されません。')) {
            return;
        }
        
        try {
            const response = await fetch('/household/category/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert(result.error || 'エラーが発生しました');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        }
    }

    // ドラッグ&ドロップで並び替え
    let draggedElement = null;
    let draggedType = null;

    // 全てのカテゴリアイテムにドラッグイベントを設定
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragleave', handleDragLeave);
    });

    function handleDragStart(e) {
        draggedElement = this;
        draggedType = this.closest('.category-list').dataset.type;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('drag-over');
        });
        
        // 並び順を保存
        saveCategoryOrder(draggedType);
    }

    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        
        const currentType = this.closest('.category-list').dataset.type;
        
        // 同じタイプのカテゴリ間でのみドロップ可能
        if (draggedType === currentType && this !== draggedElement) {
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drag-over');
        }
        
        return false;
    }

    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        const currentType = this.closest('.category-list').dataset.type;
        
        if (draggedType === currentType && draggedElement !== this) {
            const list = this.closest('.category-list');
            const items = Array.from(list.querySelectorAll('.category-item'));
            const draggedIndex = items.indexOf(draggedElement);
            const targetIndex = items.indexOf(this);
            
            if (draggedIndex < targetIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }
        }
        
        this.classList.remove('drag-over');
        return false;
    }

    function handleDragLeave(e) {
        this.classList.remove('drag-over');
    }

    // 並び順を保存
    async function saveCategoryOrder(type) {
        const listId = type === 'income' ? 'income-categories' : 'expense-categories';
        const list = document.getElementById(listId);
        const items = list.querySelectorAll('.category-item');
        const order = Array.from(items).map(item => parseInt(item.dataset.id));
        
        try {
            const response = await fetch('/household/category/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    order: order
                })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                console.log('並び順を保存しました');
            } else {
                console.error('並び順の保存に失敗しました');
                alert('並び順の保存に失敗しました');
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
            location.reload();
        }
    }

    // カテゴリ更新
    async function updateCategory(event) {
        event.preventDefault();
        
        const submitBtn = event.target.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>更新中...';
        submitBtn.disabled = true;
        
        const id = document.getElementById('edit-id').value;
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());
        delete data.id;
        
        try {
            const response = await fetch(`/household/category/update/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                alert(result.message);
                closeEditModal();
                location.reload();
            } else {
                alert(result.error || 'エラーが発生しました');
            }
         } catch (error) {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    // タブ切り替え機能
    function switchTab(tabId) {
        // すべてのタブコンテンツとボタンを非アクティブ化
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
            content.classList.remove('active');
        });
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // 選択されたタブをアクティブ化
        const selectedTab = document.getElementById(tabId);
        if (selectedTab) {
            selectedTab.style.display = 'block';
            selectedTab.classList.add('active');
        }

        const selectedButton = document.querySelector(`[data-tab="${tabId}"]`);
        if (selectedButton) {
            selectedButton.classList.add('active');
        }

        // URLハッシュを更新
        window.location.hash = tabId;
    }

    // ページロード時にURLハッシュをチェックしてタブを開く
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash.substring(1);
        if (hash === 'subscription-tab') {
            switchTab('subscription-tab');
        } else if (hash === 'budget-tab') {
            switchTab('budget-tab');
        } else {
            // デフォルトはカテゴリ管理タブ
            switchTab('category-tab');
        }
    });

    // サブスクリプション関連の関数
    function formatSubscriptionAmount(input) {
        let value = input.value.replace(/[^\d]/g, '');
        if (value) {
            input.value = parseInt(value).toLocaleString();
        }
    }

    function openAddSubscriptionModal() {
        document.getElementById('addSubscriptionModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeAddSubscriptionModal() {
        document.getElementById('addSubscriptionModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('addSubscriptionForm').reset();
    }

    function openEditSubscriptionModal(id, subscription, category, amount, day, currency) {
        document.getElementById('edit-subscription-id').value = id;
        document.getElementById('edit-subscription-name').value = subscription;
        document.getElementById('edit-subscription-category').value = category;
        document.getElementById('edit-subscription-amount').value = amount;
        document.getElementById('edit-subscription-day').value = day;
        document.getElementById('edit-subscription-currency').value = currency || 'JPY';

        document.getElementById('editSubscriptionModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeEditSubscriptionModal() {
        document.getElementById('editSubscriptionModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('editSubscriptionForm').reset();
    }

    function submitAddSubscription(event) {
        event.preventDefault();

        const submitBtn = event.target.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>登録中...';
        submitBtn.disabled = true;

        const formData = new FormData(event.target);
        const amount = formData.get('amount').replace(/,/g, '');
        formData.set('amount', amount);

        fetch('/household/subscriptions/store', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.hash = 'subscription-tab';
                location.reload();
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    function submitEditSubscription(event) {
        event.preventDefault();

        const submitBtn = event.target.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>更新中...';
        submitBtn.disabled = true;

        const id = document.getElementById('edit-subscription-id').value;
        const formData = new FormData(event.target);
        const amount = formData.get('amount').replace(/,/g, '');
        formData.set('amount', amount);

        fetch(`/household/subscriptions/update/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-HTTP-Method-Override': 'PUT',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.hash = 'subscription-tab';
                location.reload();
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    function deleteSubscription(id, name) {
        if (!confirm(`「${name}」を削除しますか？`)) {
            return;
        }

        fetch(`/household/subscriptions/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.hash = 'subscription-tab';
                location.reload();
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        });
    }

    function toggleSubscription(id, element) {
        fetch(`/household/subscriptions/toggle/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.hash = 'subscription-tab';
                location.reload();
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        });
    }

    // 金額フォーマットイベント（サブスクリプション用）
    const addSubAmount = document.getElementById('add-subscription-amount');
    if (addSubAmount) {
        addSubAmount.addEventListener('input', function() {
            formatSubscriptionAmount(this);
        });
    }

    const editSubAmount = document.getElementById('edit-subscription-amount');
    if (editSubAmount) {
        editSubAmount.addEventListener('input', function() {
            formatSubscriptionAmount(this);
        });
    }

    // モーダル外クリックで閉じる（サブスクリプション用）
    const addSubModal = document.getElementById('addSubscriptionModal');
    if (addSubModal) {
        addSubModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddSubscriptionModal();
            }
        });
    }

    const editSubModal = document.getElementById('editSubscriptionModal');
    if (editSubModal) {
        editSubModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditSubscriptionModal();
            }
        });
    }

    // ===== 予算管理関連 =====

    // 予算金額のフォーマット
    function formatBudgetAmount(input) {
        let value = input.value.replace(/,/g, '');
        if (value === '') return;
        if (!isNaN(value)) {
            input.value = Number(value).toLocaleString('ja-JP');
        }
    }

    // 予算を保存
    function saveBudget(event, budgetId, categoryName) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('.save-budget-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span>保存中...';
        submitBtn.disabled = true;

        const amountInput = form.querySelector('input[name="amount"]');
        const amount = amountInput.value.replace(/,/g, '');

        // 期間の取得
        const periodInputs = form.querySelectorAll('input[type="radio"]:checked');
        const period = periodInputs.length > 0 ? periodInputs[0].value : 'monthly';

        const data = {
            category: categoryName,
            amount: parseInt(amount) || 0,
            period: period
        };

        const url = budgetId ? `/household/budgets/update/${budgetId}` : '/household/budgets/store';
        const method = budgetId ? 'PUT' : 'POST';

        // ヘッダーを構築
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        };

        // PUTの場合のみX-HTTP-Method-Overrideを追加
        if (method === 'PUT') {
            headers['X-HTTP-Method-Override'] = 'PUT';
        }

        fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("household.settings") }}#budget-tab';
                location.reload();
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    // 予算を削除
    function deleteBudget(event, budgetId, categoryName) {
        event.preventDefault();

        if (!confirm(`「${categoryName}」の予算を削除しますか？`)) {
            return;
        }

        fetch(`/household/budgets/delete/${budgetId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-HTTP-Method-Override': 'DELETE'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("household.settings") }}#budget-tab';
                location.reload();
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        });
    }
</script>
@endsection