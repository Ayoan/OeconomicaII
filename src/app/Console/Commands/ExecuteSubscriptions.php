<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Oeconomica;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExecuteSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定期的なサブスクリプション支出を自動登録する';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("サブスクリプションの実行を開始します: {$today->toDateString()}");

        // 今日実行すべきサブスクリプションを取得
        $subscriptions = Subscription::shouldExecuteToday()->get();

        $executedCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // 収支データを作成（支出として登録）
                Oeconomica::create([
                    'user_id' => $subscription->user_id,
                    'balance' => 'expense', // サブスクリプションは支出
                    'category' => $subscription->category,
                    'amount' => $subscription->amount,
                    'memo' => '定期: ' . $subscription->subscription,
                    'date' => $today,
                ]);

                // 支払日を更新
                $subscription->update(['payday' => $today]);

                $executedCount++;
                $this->info("✓ 実行完了: {$subscription->subscription} (ユーザーID: {$subscription->user_id})");

                Log::info("サブスクリプション実行: {$subscription->subscription}", [
                    'user_id' => $subscription->user_id,
                    'amount' => $subscription->amount,
                    'category' => $subscription->category,
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ 実行失敗: {$subscription->subscription} - {$e->getMessage()}");

                Log::error("サブスクリプション実行エラー: {$subscription->subscription}", [
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\n実行完了: {$executedCount}件成功, {$errorCount}件失敗");

        return 0;
    }
}
