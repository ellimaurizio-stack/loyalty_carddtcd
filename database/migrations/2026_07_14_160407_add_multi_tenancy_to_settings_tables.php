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
        $tables = ['pwa_settings', 'loyalty_programs', 'products', 'promotional_rules', 'disclaimers'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['pwa_settings', 'loyalty_programs', 'products', 'promotional_rules', 'disclaimers'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['brand_id']);
                $t->dropColumn('brand_id');
            });
        }
    }
};
