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
        Schema::create('trading_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol');
            $table->decimal('base_order_size', 20, 8);
            $table->integer('grid_count');
            $table->decimal('grid_step', 8, 4);
            $table->decimal('martingale_percent', 8, 4);
            $table->decimal('take_profit_percent', 8, 4);
            $table->decimal('stop_loss_percent', 8, 4);
            $table->integer('leverage');
            $table->boolean('is_active')->default(false);
            $table->decimal('total_invested', 20, 8)->default(0);
            $table->decimal('total_profit', 20, 8)->default(0);
            $table->json('current_cycle_orders')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_bots');
    }
};
