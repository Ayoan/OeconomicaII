<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
        'reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'reset_token_expires' => 'datetime',
        ];
    }

    /**
     * Get the password attribute name for authentication.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'email';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    /**
     * リレーション: ユーザーのカテゴリ
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * リレーション: ユーザーの収支データ
     */
    public function oeconomicas()
    {
        return $this->hasMany(Oeconomica::class);
    }

    /**
     * リレーション: ユーザーのサブスクリプション
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * リレーション: ユーザーの予算
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }
}