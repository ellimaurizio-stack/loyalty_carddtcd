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
        Schema::create('customer_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('reward_type'); // e.g., 'physical_prize', 'discount_fixed', 'discount_percent'
            $table->string('reward_value'); // e.g., 'T-Shirt', '5', '10'
            $table->string('description')->nullable();
            $table->boolean('is_redeemed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_rewards');
    }
};
