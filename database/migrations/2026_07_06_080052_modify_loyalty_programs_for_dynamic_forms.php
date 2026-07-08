<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn([
                'require_name',
                'require_surname',
                'require_email',
                'disclaimer_text'
            ]);
            
            $table->json('form_fields')->nullable();
            $table->string('background_image_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->boolean('require_name')->default(false);
            $table->boolean('require_surname')->default(false);
            $table->boolean('require_email')->default(false);
            $table->text('disclaimer_text')->nullable();
            
            $table->dropColumn(['form_fields', 'background_image_path']);
        });
    }
};
