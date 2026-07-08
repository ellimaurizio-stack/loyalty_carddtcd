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
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->string('otp_channel')->default('phone');
            $table->string('otp_channel_label')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn(['otp_channel', 'otp_channel_label']);
        });
    }
};
