<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('selection_type', 20)->default('single');
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['product_id', 'name']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_groups');
    }
};
