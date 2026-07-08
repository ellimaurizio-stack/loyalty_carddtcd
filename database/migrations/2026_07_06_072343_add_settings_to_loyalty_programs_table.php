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
            $table->boolean('require_name')->default(false);
            $table->boolean('require_surname')->default(false);
            $table->boolean('require_email')->default(false);
            $table->text('disclaimer_text')->nullable();
            $table->string('background_color')->default('#ffffff');
            $table->string('primary_color')->default('#3f51b5');
            $table->string('logo_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn([
                'require_name',
                'require_surname',
                'require_email',
                'disclaimer_text',
                'background_color',
                'primary_color',
                'logo_path'
            ]);
        });
    }
};
