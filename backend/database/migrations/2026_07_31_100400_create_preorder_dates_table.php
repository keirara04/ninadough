<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preorder_dates', function (Blueprint $table) {
            $table->id();
            $table->date('order_date')->unique();
            $table->timestampTz('cutoff_at');
            $table->integer('capacity_limit');
            $table->integer('reserved_capacity')->default(0);
            $table->boolean('pickup_enabled')->default(true);
            $table->boolean('delivery_enabled')->default(true);
            $table->string('status', 20)->default('open');
            $table->string('note_internal', 500)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['status', 'order_date']);
        });

        DB::statement("ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_status_check CHECK (status IN ('open', 'closed', 'full'))");
        DB::statement('ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_capacity_limit_check CHECK (capacity_limit > 0)');
        DB::statement('ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_reserved_capacity_check CHECK (reserved_capacity >= 0)');
        DB::statement('ALTER TABLE preorder_dates ADD CONSTRAINT preorder_dates_reserved_within_limit_check CHECK (reserved_capacity <= capacity_limit)');
    }

    public function down(): void
    {
        Schema::dropIfExists('preorder_dates');
    }
};
