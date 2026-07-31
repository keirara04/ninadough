<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->bigInteger('delivery_fee_sen');
            $table->bigInteger('minimum_order_sen')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['is_active', 'sort_order']);
        });

        DB::statement('ALTER TABLE delivery_zones ADD CONSTRAINT delivery_zones_fee_check CHECK (delivery_fee_sen >= 0)');
        DB::statement('ALTER TABLE delivery_zones ADD CONSTRAINT delivery_zones_min_order_check CHECK (minimum_order_sen IS NULL OR minimum_order_sen >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
