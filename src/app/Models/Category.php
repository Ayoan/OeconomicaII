<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category',
        'balance',
        'type',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * リレーション: カテゴリの所有者
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション: このカテゴリに属する収支データ
     */
    public function oeconomicas()
    {
        return $this->hasMany(Oeconomica::class, 'category', 'category');
    }

    /**
     * スコープ: 収入カテゴリのみ取得
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * スコープ: 支出カテゴリのみ取得
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * スコープ: 特定ユーザーのカテゴリのみ取得
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}