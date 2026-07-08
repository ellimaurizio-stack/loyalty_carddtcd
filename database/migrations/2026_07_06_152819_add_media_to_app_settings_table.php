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
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('cart_icon_color')->default('#42A5F5'); // Colors.blueAccent
            $table->string('logo_path')->nullable();
            $table->string('background_image_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['cart_icon_color', 'logo_path', 'background_image_path']);
        });
    }
};
