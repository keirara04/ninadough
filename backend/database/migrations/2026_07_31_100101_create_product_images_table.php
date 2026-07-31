<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 50);
            $table->string('object_key', 500);
            $table->string('public_url', 1000)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();

            $table->index(['product_id', 'sort_order']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX product_images_one_primary_per_product '.
            'ON product_images (product_id) WHERE is_primary = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
