<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
        });

        DB::statement(
            'ALTER TABLE product_variants ADD CONSTRAINT product_variants_stock_quantity_check '.
            'CHECK (stock_quantity >= 0)'
        );
        DB::statement(
            'ALTER TABLE product_variants ADD CONSTRAINT product_variants_reserved_quantity_check '.
            'CHECK (reserved_quantity >= 0 AND reserved_quantity <= stock_quantity)'
        );
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'reserved_quantity']);
        });
    }
};
