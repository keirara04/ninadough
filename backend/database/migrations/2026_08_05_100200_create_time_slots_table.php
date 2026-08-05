<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_date_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('fulfilment_method', 20);
            $table->integer('capacity_limit');
            $table->integer('reserved_capacity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['preorder_date_id', 'fulfilment_method', 'is_active']);
        });

        DB::statement("ALTER TABLE time_slots ADD CONSTRAINT time_slots_fulfilment_method_check CHECK (fulfilment_method IN ('pickup', 'delivery', 'both'))");
        DB::statement('ALTER TABLE time_slots ADD CONSTRAINT time_slots_capacity_limit_check CHECK (capacity_limit > 0)');
        DB::statement('ALTER TABLE time_slots ADD CONSTRAINT time_slots_reserved_capacity_check CHECK (reserved_capacity >= 0)');
        DB::statement('ALTER TABLE time_slots ADD CONSTRAINT time_slots_reserved_within_limit_check CHECK (reserved_capacity <= capacity_limit)');
        DB::statement('ALTER TABLE time_slots ADD CONSTRAINT time_slots_time_range_check CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
