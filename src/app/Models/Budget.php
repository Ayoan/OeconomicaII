<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'amount',
        'period',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    /**
     * リレーション: ユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * スコープ: 月単位の予算のみ
     */
    public function scopeMonthly($query)
    {
        return $query->where('period', 'monthly');
    }

    /**
     * スコープ: 年単位の予算のみ
     */
    public function scopeYearly($query)
    {
        return $query->where('period', 'yearly');
    }

    /**
     * 期間を日本語で取得
     */
    public function getPeriodTextAttribute()
    {
        return $this->period === 'monthly' ? '月単位' : '年単位';
    }

    /**
     * 月次実績を取得（指定された年月のカテゴリ別支出額）
     */
    public function getMonthlyActualAmount($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return Oeconomica::where('user_id', $this->user_id)
            ->where('balance', 'expense')
            ->where('category', $this->category)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * 年次実績を取得（指定された年のカテゴリ別支出額）
     */
    public function getYearlyActualAmount($year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = Carbon::create($year, 12, 31)->endOfYear();

        return Oeconomica::where('user_id', $this->user_id)
            ->where('balance', 'expense')
            ->where('category', $this->category)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * 月次の達成率を計算（実績 / 予算 * 100）
     */
    public function getMonthlyAchievementRate($year, $month)
    {
        if ($this->amount == 0) {
            return 0;
        }

        $actual = $this->getMonthlyActualAmount($year, $month);
        return round(($actual / $this->amount) * 100, 1);
    }

    /**
     * 年次の達成率を計算
     */
    public function getYearlyAchievementRate($year)
    {
        $budgetAmount = $this->period === 'monthly' ? $this->amount * 12 : $this->amount;

        if ($budgetAmount == 0) {
            return 0;
        }

        $actual = $this->getYearlyActualAmount($year);
        return round(($actual / $budgetAmount) * 100, 1);
    }

    /**
     * 年間予算額を取得（月単位の場合は*12）
     */
    public function getYearlyBudgetAmount()
    {
        return $this->period === 'monthly' ? $this->amount * 12 : $this->amount;
    }
}
