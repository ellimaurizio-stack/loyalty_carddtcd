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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bg_color')->default('#FFFFFF');
            $table->string('header_color')->default('#3F51B5');
            $table->string('header_text')->default('Cassa Rapida');
            $table->string('header_text_color')->default('#FFFFFF');
            $table->string('pay_btn_color')->default('#4CAF50');
            $table->string('pay_btn_text')->default('Paga con NFC');
            $table->string('pay_btn_text_color')->default('#FFFFFF');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
