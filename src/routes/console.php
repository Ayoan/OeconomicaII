<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// サブスクリプションの自動実行スケジュール
Schedule::command('subscriptions:execute')
    ->daily()
    ->timezone('Asia/Tokyo');
