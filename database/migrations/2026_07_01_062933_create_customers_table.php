<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('card_identifier')->unique();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable(); // Nullable initially for anonymous purchases
            $table->integer('loyalty_points')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
