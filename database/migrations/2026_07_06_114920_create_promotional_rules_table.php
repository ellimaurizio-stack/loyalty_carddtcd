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
        Schema::create('promotional_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->json('parameters')->nullable();
            $table->json('conditions')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_stackable')->default(true);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_rules');
    }
};
