<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワードリセットのお知らせ</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        
        .email-body {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .message {
            margin-bottom: 30px;
            line-height: 1.8;
            color: #555;
        }
        
        .reset-button {
            text-align: center;
            margin: 40px 0;
        }
        
        .reset-link {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .reset-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .info-section {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .info-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .url-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
            color: #666;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .email-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-logo {
            margin-bottom: 15px;
            font-size: 18px;
            color: #667eea;
            font-weight: 500;
        }
        
        @media (max-width: 600px) {
            .email-body {
                padding: 30px 20px;
            }
            
            .reset-link {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🏠 家計簿管理システム</h1>
        </div>
        
        <div class="email-body">
            <div class="greeting">
                {{ $user->username }} 様
            </div>
            
            <div class="message">
                <p>いつも家計簿管理システムをご利用いただき、ありがとうございます。</p>
                <p>パスワードリセットのご依頼を受け付けました。下記のボタンをクリックして、新しいパスワードを設定してください。</p>
            </div>
            
            <div class="reset-button">
                <a href="{{ $resetUrl }}" class="reset-link">
                    🔄 パスワードを変更する
                </a>
            </div>
            
            <div class="info-section">
                <div class="info-title">📋 重要な情報</div>
                <ul>
                    <li>このリンクの有効期限は<strong>1時間</strong>です</li>
                    <li>リンクは一度のみ使用可能です</li>
                    <li>パスワード変更後、このリンクは無効になります</li>
                </ul>
            </div>
            
            <div class="message">
                <p>上記のボタンが機能しない場合は、以下のURLを直接ブラウザにコピー＆ペーストしてください：</p>
            </div>
            
            <div class="url-section">
                {{ $resetUrl }}
            </div>
            
            <div class="warning">
                <strong>⚠️ セキュリティについて</strong><br>
                もしこのパスワードリセットにお心当たりがない場合は、このメールを無視してください。第三者がアカウントにアクセスすることはありません。
            </div>
            
            <div class="message">
                <p>ご不明な点がございましたら、サポートまでお気軽にお問い合わせください。</p>
                <p>今後ともよろしくお願いいたします。</p>
            </div>
        </div>
        
        <div class="email-footer">
            <div class="footer-logo">家計簿管理システム</div>
            <p>このメールは自動送信されています。返信はできません。</p>
            <p>&copy; {{ date('Y') }} 家計簿管理システム. All rights reserved.</p>
        </div>
    </div>
</body>
</html>