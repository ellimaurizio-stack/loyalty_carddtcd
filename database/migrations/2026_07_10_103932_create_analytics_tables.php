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
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id('customer_id'); // Actually a primary key linked to customers, but treating as separate entity
            $table->string('segment_name')->nullable();
            $table->float('recency')->nullable();
            $table->float('frequency')->nullable();
            $table->float('monetary')->nullable();
            $table->timestamps();
        });

        Schema::create('product_associations', function (Blueprint $table) {
            $table->id();
            $table->text('antecedents')->nullable();
            $table->text('consequents')->nullable();
            $table->float('support')->nullable();
            $table->float('confidence')->nullable();
            $table->float('lift')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_associations');
        Schema::dropIfExists('customer_segments');
    }
};
