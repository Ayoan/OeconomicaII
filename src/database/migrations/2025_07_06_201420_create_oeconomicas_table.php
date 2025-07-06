<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_oeconomicas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOeconomicasTable extends Migration
{
    public function up()
    {
        Schema::create('oeconomicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('balance')->nullable(); // 支出/収入
            $table->date('date')->nullable();
            $table->string('category')->nullable();
            $table->integer('amount')->nullable();
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'balance']);
            $table->index(['user_id', 'date', 'balance']);
            $table->index(['user_id', 'category', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('oeconomicas');
    }
}
