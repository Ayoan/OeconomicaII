<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\RateLimiter;

class DebugRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:rate-limit {action=status} {ip?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug rate limiting functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $ip = $this->argument('ip') ?? '127.0.0.1';
        $key = 'login.' . $ip;

        switch ($action) {
            case 'status':
                $this->showStatus($key, $ip);
                break;
                
            case 'clear':
                $this->clearRateLimit($key, $ip);
                break;
                
            case 'simulate':
                $this->simulateFailures($key, $ip);
                break;
                
            case 'test':
                $this->testRateLimit($key, $ip);
                break;
                
            default:
                $this->error('無効なアクション: ' . $action);
                $this->info('利用可能なアクション: status, clear, simulate, test');
                break;
        }
    }

    /**
     * Show current rate limit status
     */
    private function showStatus($key, $ip)
    {
        $attempts = RateLimiter::attempts($key);
        $maxAttempts = 5;
        $remainingAttempts = max(0, $maxAttempts - $attempts);
        $availableIn = RateLimiter::availableIn($key);
        $tooMany = RateLimiter::tooManyAttempts($key, $maxAttempts);

        $this->info("=== レート制限ステータス ===");
        $this->info("IP: {$ip}");
        $this->info("Key: {$key}");
        $this->info("現在の試行回数: {$attempts}");
        $this->info("最大試行回数: {$maxAttempts}");
        $this->info("残り試行回数: {$remainingAttempts}");
        
        if ($tooMany) {
            $minutes = ceil($availableIn / 60);
            $this->error("❌ レート制限中");
            $this->error("解除まで: {$availableIn}秒 ({$minutes}分)");
        } else {
            $this->info("✅ 制限なし");
        }
    }

    /**
     * Clear rate limit for the given key
     */
    private function clearRateLimit($key, $ip)
    {
        RateLimiter::clear($key);
        $this->info("✅ IP {$ip} のレート制限をクリアしました");
        $this->showStatus($key, $ip);
    }

    /**
     * Simulate login failures
     */
    private function simulateFailures($key, $ip)
    {
        $count = $this->ask('何回の失敗をシミュレートしますか？', 3);
        
        for ($i = 1; $i <= $count; $i++) {
            RateLimiter::hit($key, 15 * 60); // 15分間
            $this->info("失敗 {$i} 回目をシミュレート");
        }
        
        $this->info("シミュレーション完了");
        $this->showStatus($key, $ip);
    }

    /**
     * Test rate limiting functionality
     */
    private function testRateLimit($key, $ip)
    {
        $this->info("=== レート制限テスト開始 ===");
        
        // 現在の状態を表示
        $this->showStatus($key, $ip);
        
        // 5回失敗をシミュレート
        $this->info("\n5回の失敗をシミュレート中...");
        for ($i = 1; $i <= 5; $i++) {
            RateLimiter::hit($key, 15 * 60);
            $attempts = RateLimiter::attempts($key);
            $this->info("失敗 {$i} 回目 (合計: {$attempts} 回)");
        }
        
        // 制限状態を確認
        $this->info("\n制限後のステータス:");
        $this->showStatus($key, $ip);
        
        // 6回目の試行をテスト
        $this->info("\n6回目の試行をテスト:");
        $tooMany = RateLimiter::tooManyAttempts($key, 5);
        
        if ($tooMany) {
            $this->error("✅ レート制限が正常に機能しています");
        } else {
            $this->error("❌ レート制限が機能していません");
        }
        
        // クリーンアップ
        if ($this->confirm('テストデータをクリアしますか？')) {
            RateLimiter::clear($key);
            $this->info("テストデータをクリアしました");
        }
    }
}