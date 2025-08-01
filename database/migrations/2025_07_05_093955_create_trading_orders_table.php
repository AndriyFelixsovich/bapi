<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trading_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_bot_id')->constrained()->onDelete('cascade');
            $table->string('bybit_order_id')->nullable();
            $table->string('symbol');
            $table->enum('side', ['Buy', 'Sell']);
            $table->enum('order_type', ['Market', 'Limit']);
            $table->decimal('qty', 20, 8);
            $table->decimal('price', 20, 8)->nullable();
            $table->enum('status', ['New', 'Filled', 'Cancelled', 'Rejected']);
            $table->decimal('executed_qty', 20, 8)->default(0);
            $table->decimal('executed_price', 20, 8)->nullable();
            $table->integer('order_level');
            $table->boolean('is_take_profit')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_orders');
    }
};
