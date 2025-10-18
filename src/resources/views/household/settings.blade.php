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
</script>
@endsection