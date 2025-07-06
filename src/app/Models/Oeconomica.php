<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Oeconomica extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oeconomicas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'date',
        'category',
        'amount',
        'memo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * リレーション: 収支データの所有者
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション: カテゴリ（参考用）
     */
    public function categoryModel()
    {
        return $this->belongsTo(Category::class, 'category', 'category')
            ->where('user_id', $this->user_id);
    }

    /**
     * スコープ: 収入のみ取得
     */
    public function scopeIncome($query)
    {
        return $query->where('balance', 'income');
    }

    /**
     * スコープ: 支出のみ取得
     */
    public function scopeExpense($query)
    {
        return $query->where('balance', 'expense');
    }

    /**
     * スコープ: 特定ユーザーのデータのみ取得
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * スコープ: 特定月のデータのみ取得
     */
    public function scopeForMonth($query, $year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * スコープ: 特定年のデータのみ取得
     */
    public function scopeForYear($query, $year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = Carbon::create($year, 12, 31)->endOfYear();
        
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * スコープ: 日付範囲でデータ取得
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * 金額をフォーマットして取得
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount);
    }

    /**
     * 収支区分の日本語表示
     */
    public function getBalanceTextAttribute()
    {
        return $this->balance === 'income' ? '収入' : '支出';
    }

    /**
     * 収支区分に応じた符号付き金額
     */
    public function getSignedAmountAttribute()
    {
        $sign = $this->balance === 'income' ? '+' : '-';
        return $sign . number_format($this->amount);
    }

    /**
     * 月日表示（MM/DD形式）
     */
    public function getShortDateAttribute()
    {
        return $this->date->format('m/d');
    }
}