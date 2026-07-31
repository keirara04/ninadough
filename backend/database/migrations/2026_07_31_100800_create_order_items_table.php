<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot', 160);
            $table->string('variant_name_snapshot', 180)->nullable();
            $table->string('sku_snapshot', 100)->nullable();
            $table->bigInteger('unit_price_sen');
            $table->integer('quantity');
            $table->integer('capacity_units_each');
            $table->bigInteger('line_total_sen');
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['order_id', 'sort_order']);
        });

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_unit_price_check CHECK (unit_price_sen >= 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_capacity_units_each_check CHECK (capacity_units_each > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_line_total_check CHECK (line_total_sen >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
