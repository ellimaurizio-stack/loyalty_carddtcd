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
        Schema::table('pwa_settings', function (Blueprint $table) {
            $table->string('card_color')->default('#4f46e5')->nullable();
            $table->string('card_text_color')->default('#ffffff')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pwa_settings', function (Blueprint $table) {
            $table->dropColumn(['card_color', 'card_text_color']);
        });
    }
};
