<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->nullable()->unique();
            $table->string('name', 180);
            $table->bigInteger('price_adjustment_sen')->default(0);
            $table->integer('capacity_units')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['product_id', 'is_active', 'sort_order']);
        });

        DB::statement(
            'ALTER TABLE product_variants ADD CONSTRAINT product_variants_capacity_units_check '.
            'CHECK (capacity_units IS NULL OR capacity_units > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
