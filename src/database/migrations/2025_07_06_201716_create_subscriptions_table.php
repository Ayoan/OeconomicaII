<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('day')->nullable();
            $table->string('category')->nullable();
            $table->string('subscription')->nullable();
            $table->float('amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('payday')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_active']);
            $table->index(['payday', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
