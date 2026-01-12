<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('category');
            $table->integer('amount');
            $table->enum('period', ['monthly', 'yearly']);
            $table->timestamps();

            // インデックス
            $table->index(['user_id', 'category']);
            $table->index('period');

            // ユニーク制約（同じユーザー・カテゴリの組み合わせは1つのみ）
            $table->unique(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budgets');
    }
}
