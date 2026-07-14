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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('registration_store_id')->nullable()->constrained('stores')->nullOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('customer_rewards', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['registration_store_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['registration_store_id', 'brand_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['store_id', 'brand_id']);
        });

        Schema::table('customer_rewards', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['store_id', 'brand_id']);
        });
    }
};
