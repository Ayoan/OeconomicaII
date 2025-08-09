@extends('layouts.app')

@section('title', 'CSVインポート - 家計簿管理システム')

@section('content')
<div class="import-wrapper">
    <div class="container">
        <!-- ヘッダー部分 -->
        <div class="page-header">
            <h1 class="page-title">
                <span class="title-icon">📤</span>
                CSVインポート
            </h1>
            <p class="page-subtitle">CSVファイルから家計簿データを一括登録できます</p>
        </div>

        <!-- 戻るボタン -->
        <div class="back-button-container">
            <a href="{{ route('household.input') }}" class="back-btn">
                <span class="btn-icon">←</span>
                入力画面に戻る
            </a>
        </div>

        <!-- 成功・エラーメッセージ -->
        @if (session('success'))
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                <span class="alert-icon">⚠️</span>
                {{ session('warning') }}
            </div>
        @endif

        @if (session('import_errors'))
            <div class="alert alert-danger">
                <span class="alert-icon">❌</span>
                <strong>インポートエラーの詳細:</strong>
                <ul class="error-list">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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

        <!-- CSVフォーマット説明 -->
        <div class="format-card">
            <h2 class="card-title">
                <span class="card-icon">📋</span>
                CSVファイルの形式
            </h2>
            
            <div class="format-content">
                <p class="format-description">
                    以下の形式でCSVファイルを作成してください。1行目はヘッダー行として除外されます。
                </p>
                
                <div class="format-example">
                    <h3>📄 ファイル例</h3>
                    <div class="csv-example">
                        <pre>日付,収支区分,カテゴリ,金額,メモ
2025/07/01,収入,給与,300000,7月分給与
2025/07/02,支出,食費,1500,昼食
2025/07/03,支出,交通費,800,電車代</pre>
                    </div>
                </div>
                
                <div class="format-rules">
                    <h3>📝 入力ルール</h3>
                    <ul>
                        <li><strong>日付</strong>: YYYY/MM/DD または YYYY-MM-DD 形式</li>
                        <li><strong>収支区分</strong>: 「収入」または「支出」</li>
                        <li><strong>カテゴリ</strong>: 登録済みカテゴリの名前</li>
                        <li><strong>金額</strong>: 1以上の数値（カンマなし）</li>
                        <li><strong>メモ</strong>: 255文字以内（省略可能）</li>
                    </ul>
                </div>
                
                <div class="available-categories">
                    <h3>🏷️ 利用可能なカテゴリ</h3>
                    <div class="categories-grid">
                        <div class="category-group">
                            <h4>収入カテゴリ</h4>
                            <ul>
                                @foreach($incomeCategories as $category)
                                    <li>{{ $category->category }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="category-group">
                            <h4>支出カテゴリ</h4>
                            <ul>
                                @foreach($expenseCategories as $category)
                                    <li>{{ $category->category }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- インポートフォーム -->
        <div class="import-form-card">
            <h2 class="card-title">
                <span class="card-icon">📂</span>
                ファイル選択・インポート
            </h2>
            
            <form method="POST" action="{{ route('household.import.store') }}" enctype="multipart/form-data" class="import-form">
                @csrf
                
                <div class="file-input-group">
                    <label for="csv_file" class="file-label">CSVファイルを選択</label>
                    <input type="file" 
                           id="csv_file" 
                           name="csv_file" 
                           class="file-input"
                           accept=".csv,.txt"
                           required>
                    <div class="file-info">
                        <span class="file-size">最大ファイルサイズ: 2MB</span>
                        <span class="file-format">対応形式: .csv, .txt</span>
                    </div>
                </div>
                
                <div class="import-warnings">
                    <h3>⚠️ 注意事項</h3>
                    <ul>
                        <li>インポート前に必ずデータのバックアップを取得してください</li>
                        <li>重複するデータがある場合、両方とも登録されます</li>
                        <li>エラーのある行はスキップされ、正常な行のみインポートされます</li>
                        <li>大量データの場合、処理に時間がかかることがあります</li>
                    </ul>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="import-btn">
                        <span class="btn-icon">📤</span>
                        インポート実行
                    </button>
                </div>
            </form>
        </div>

        <!-- サンプルダウンロード -->
        <div class="sample-card">
            <h2 class="card-title">
                <span class="card-icon">💾</span>
                サンプルファイル
            </h2>
            
            <p class="sample-description">
                CSVの作成が初めての方は、サンプルファイルをダウンロードして参考にしてください。
            </p>
            
            <button id="downloadSample" class="sample-btn">
                <span class="btn-icon">⬇️</span>
                サンプルCSVをダウンロード
            </button>
        </div>
    </div>
</div>

<style>
    .import-wrapper {
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

    .back-button-container {
        margin-bottom: 20px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: white;
        color: #667eea;
        text-decoration: none;
        border-radius: 8px;
        border: 2px solid #667eea;
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .back-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    /* アラート */
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
    }

    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
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
        flex: 1;
    }

    .error-list li {
        margin-bottom: 5px;
    }

    /* カード共通スタイル */
    .format-card,
    .import-form-card,
    .sample-card {
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

    /* フォーマット説明 */
    .format-content {
        padding: 0 30px 30px;
    }

    .format-description {
        color: #666;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .format-example {
        margin-bottom: 25px;
    }

    .format-example h3 {
        font-size: 16px;
        color: #333;
        margin-bottom: 10px;
    }

    .csv-example {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        overflow-x: auto;
    }

    .csv-example pre {
        margin: 0;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        color: #333;
        white-space: pre;
    }

    .format-rules {
        margin-bottom: 25px;
    }

    .format-rules h3,
    .available-categories h3,
    .import-warnings h3 {
        font-size: 16px;
        color: #333;
        margin-bottom: 15px;
    }

    .format-rules ul,
    .import-warnings ul {
        padding-left: 20px;
        color: #666;
        line-height: 1.6;
    }

    .format-rules li,
    .import-warnings li {
        margin-bottom: 8px;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    .category-group h4 {
        font-size: 14px;
        color: #333;
        margin-bottom: 10px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .category-group ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .category-group li {
        padding: 6px 12px;
        border-bottom: 1px solid #f0f0f0;
        color: #666;
        font-size: 14px;
    }

    /* インポートフォーム */
    .import-form {
        padding: 0 30px 30px;
    }

    .file-input-group {
        margin-bottom: 25px;
    }

    .file-label {
        display: block;
        font-size: 16px;
        color: #333;
        font-weight: 500;
        margin-bottom: 10px;
    }

    .file-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px dashed #e1e8ed;
        border-radius: 8px;
        background: #f8f9fa;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    .file-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
    }

    .file-info {
        display: flex;
        gap: 20px;
        margin-top: 8px;
        font-size: 12px;
        color: #666;
    }

    .import-warnings {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .form-actions {
        text-align: center;
    }

    .import-btn {
        padding: 15px 40px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .import-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    /* サンプルカード */
    .sample-card {
        padding: 30px;
        text-align: center;
    }

    .sample-description {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .sample-btn {
        padding: 12px 24px;
        border: 2px solid #17a2b8;
        background: white;
        color: #17a2b8;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }

    .sample-btn:hover {
        background: #17a2b8;
        color: white;
        transform: translateY(-2px);
    }

    /* レスポンシブ */
    @media (max-width: 768px) {
        .container {
            padding: 0 15px;
        }

        .page-title {
            font-size: 24px;
        }

        .categories-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .file-info {
            flex-direction: column;
            gap: 5px;
        }
    }
</style>

<script>
    // サンプルCSVダウンロード
    document.getElementById('downloadSample').addEventListener('click', function() {
        const csvContent = `日付,収支区分,カテゴリ,金額,メモ
2025/07/01,収入,給与,300000,7月分給与
2025/07/02,支出,食費,1500,昼食代
2025/07/03,支出,交通費,800,電車代
2025/07/04,支出,日用品,2000,洗剤・ティッシュ
2025/07/05,収入,その他,5000,臨時収入`;

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', 'sample_household_data.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // ファイル選択時の表示
    document.getElementById('csv_file').addEventListener('change', function() {
        const fileName = this.files[0]?.name;
        if (fileName) {
            // ファイル名を表示する処理を追加できます
            console.log('選択されたファイル:', fileName);
        }
    });
</script>
@endsection