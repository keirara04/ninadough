<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('option_group_name_snapshot', 80);
            $table->string('option_value_name_snapshot', 120);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['order_item_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_option_values');
    }
};
