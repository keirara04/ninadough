<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('base_price_sen');
            $table->integer('default_capacity_units')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('allergen_information')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['is_active', 'sort_order']);
            $table->index(['is_featured', 'is_active']);
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_base_price_sen_check CHECK (base_price_sen >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_default_capacity_units_check CHECK (default_capacity_units > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
