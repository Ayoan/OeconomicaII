<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'day',
        'category',
        'subscription',
        'amount',
        'is_active',
        'payday',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount' => 'float',
        'payday' => 'date',
        'day' => 'integer',
    ];

    /**
     * リレーション: ユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * スコープ: 有効なサブスクリプションのみ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * スコープ: 今日実行すべきサブスクリプション
     */
    public function scopeShouldExecuteToday($query)
    {
        $today = Carbon::today();
        $currentDay = $today->day;

        return $query->active()
            ->where('day', $currentDay)
            ->where(function ($q) use ($today) {
                // 今日まだ実行されていない（paydayが今日より前、またはnull）
                $q->whereNull('payday')
                  ->orWhere('payday', '<', $today);
            });
    }

    /**
     * 次回実行日を取得
     */
    public function getNextExecutionDateAttribute()
    {
        $today = Carbon::today();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // 今月の指定日
        $nextDate = Carbon::create($currentYear, $currentMonth, min($this->day, $today->daysInMonth));

        // 今月の指定日が既に過ぎている場合は来月
        if ($nextDate->lt($today)) {
            $nextMonth = $today->copy()->addMonth();
            $nextDate = Carbon::create($nextMonth->year, $nextMonth->month, min($this->day, $nextMonth->daysInMonth));
        }

        return $nextDate;
    }

    /**
     * 実行日を人間が読みやすい形式で取得
     */
    public function getExecutionDayTextAttribute()
    {
        return "毎月{$this->day}日";
    }
}
