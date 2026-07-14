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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('brand_manager')->after('email');
            $table->foreignId('brand_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['role', 'brand_id', 'store_id']);
        });
    }
};
