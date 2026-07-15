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
            $table->unsignedTinyInteger('recency_score')->nullable();
            $table->unsignedTinyInteger('frequency_score')->nullable();
            $table->unsignedTinyInteger('monetary_score')->nullable();
            $table->string('rfm_segment', 50)->nullable();
            $table->timestamp('rfm_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'recency_score',
                'frequency_score',
                'monetary_score',
                'rfm_segment',
                'rfm_updated_at'
            ]);
        });
    }
};
