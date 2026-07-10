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
            $table->json('registration_fields')->nullable();
            $table->text('privacy_policy')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pwa_settings', function (Blueprint $table) {
            $table->dropColumn(['registration_fields', 'privacy_policy']);
        });
    }
};
