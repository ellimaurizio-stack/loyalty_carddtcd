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
        $tables = ['app_settings', 'pwa_settings', 'loyalty_programs'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['app_settings', 'pwa_settings', 'loyalty_programs'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['store_id']);
                $t->dropColumn('store_id');
            });
        }
    }
};
