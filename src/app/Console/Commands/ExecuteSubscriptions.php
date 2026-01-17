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

        // 為替レートサービスを取得
        $exchangeRateService = app(\App\Services\ExchangeRateService::class);

        // 為替レート表示（USDサブスクリプションがある場合のみ）
        $hasUsd = $subscriptions->contains('currency', 'USD');
        if ($hasUsd) {
            $rate = $exchangeRateService->getUsdToJpyRate();
            $this->info("現在の為替レート: 1 USD = {$rate} JPY");
        }

        foreach ($subscriptions as $subscription) {
            try {
                // 円換算の金額を取得
                $jpyAmount = $subscription->jpy_amount;

                // メモを生成
                $memo = '定期: ' . $subscription->subscription;
                // USDの場合は為替情報を追加
                if ($subscription->currency === 'USD') {
                    $memo .= " ({$subscription->formatted_amount})";
                }

                // 収支データを作成（円で登録）
                Oeconomica::create([
                    'user_id' => $subscription->user_id,
                    'balance' => 'expense', // サブスクリプションは支出
                    'category' => $subscription->category,
                    'amount' => $jpyAmount,
                    'memo' => $memo,
                    'date' => $today,
                ]);

                // 支払日を更新
                $subscription->update(['payday' => $today]);

                $executedCount++;
                $this->info("✓ 実行完了: {$subscription->subscription} ({$subscription->formatted_amount} → ¥" . number_format($jpyAmount) . ")");

                Log::info("サブスクリプション実行: {$subscription->subscription}", [
                    'user_id' => $subscription->user_id,
                    'original_amount' => $subscription->amount,
                    'currency' => $subscription->currency,
                    'jpy_amount' => $jpyAmount,
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
