<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zone_postcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->string('postcode', 20)->unique();
            $table->timestampsTz();

            $table->index(['delivery_zone_id', 'postcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zone_postcodes');
    }
};
