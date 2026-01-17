<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * USD/JPYの為替レートを取得
     *
     * @return float
     */
    public function getUsdToJpyRate(): float
    {
        // キャッシュキー
        $cacheKey = 'exchange_rate_usd_jpy';

        // キャッシュから取得（1時間有効）
        return Cache::remember($cacheKey, 3600, function () {
            return $this->fetchExchangeRate();
        });
    }

    /**
     * 外部APIから為替レートを取得
     *
     * @return float
     */
    private function fetchExchangeRate(): float
    {
        try {
            // 無料の為替レートAPI（exchangerate-api.com）を使用
            $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/USD');

            if ($response->successful()) {
                $data = $response->json();
                $rate = $data['rates']['JPY'] ?? null;

                if ($rate) {
                    // 異常な為替レート検知（100円〜200円の範囲外の場合）
                    if ($rate < 100 || $rate > 200) {
                        Log::warning('異常な為替レート検知', ['rate' => $rate]);
                        return $this->getDefaultRate();
                    }

                    Log::info('為替レート取得成功', ['rate' => $rate]);
                    return (float) $rate;
                }
            }

            Log::warning('為替レートAPI取得失敗、デフォルト値を使用');
            return $this->getDefaultRate();

        } catch (\Exception $e) {
            Log::error('為替レート取得エラー', [
                'error' => $e->getMessage()
            ]);

            return $this->getDefaultRate();
        }
    }

    /**
     * デフォルトの為替レート（API失敗時のフォールバック）
     *
     * @return float
     */
    private function getDefaultRate(): float
    {
        // フォールバック値（おおよその平均レート）
        return 150.0;
    }

    /**
     * ドルを円に変換
     *
     * @param float $usdAmount
     * @return int
     */
    public function convertUsdToJpy(float $usdAmount): int
    {
        $rate = $this->getUsdToJpyRate();
        return (int) round($usdAmount * $rate);
    }
}
